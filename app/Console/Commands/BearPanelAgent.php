<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\ConsoleBus;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BearpanelAgent extends Command
{
    protected $signature = 'bearpanel:agent {--debug} {--interval=1}';
    protected $description = 'Streams docker logs for running servers and broadcasts to console.';

    public function handle(ConsoleBus $bus): int
    {
        $interval = max(1, (int)$this->option('interval'));
        $debug = (bool)$this->option('debug');

        $this->info("BearPanel agent started. interval={$interval}s");

        while (true) {
            $running = Server::query()
                ->where('status', 'running')
                ->whereNotNull('container_id')
                ->get();

            if ($debug) $this->line('['.now()->format('H:i:s').'] servers running: ' . $running->count());

            foreach ($running as $server) {
                try {
                    $this->pullDockerLogs($server, $bus, $debug);
                } catch (\Throwable $e) {
                    if ($debug) $this->error($e->getMessage());
                    $bus->push($server, "[agent][error] ".$e->getMessage(), 'err');
                }
            }

            sleep($interval);
        }

        // unreachable
        // return self::SUCCESS;
    }

    private function pullDockerLogs(Server $server, ConsoleBus $bus, bool $debug): void
    {
        $server->refresh();

        $id = $server->container_id;
        if (!$id) return;

        // cursor = unix timestamp seconds
        $since = $bus->getCursor($server);
        if ($since <= 0) {
            // первый раз — берём последние 80 строк, без since
            $cmd = ['bash', '-lc', "docker logs --timestamps --tail 80 ".escapeshellarg($id)." 2>&1"];
        } else {
            $cmd = ['bash', '-lc', "docker logs --timestamps --since ".escapeshellarg((string)$since)." ".escapeshellarg($id)." 2>&1"];
        }

        $p = new Process($cmd);
        $p->setTimeout(10);
        $p->run();

        if (!$p->isSuccessful()) {
            throw new \RuntimeException(trim($p->getErrorOutput()) ?: trim($p->getOutput()) ?: 'docker logs failed');
        }

        $out = trim($p->getOutput());
        if ($out === '') return;

        $lines = preg_split('/\r\n|\r|\n/', $out);
        $maxTs = $since;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // docker --timestamps: "2026-01-25T14:45:10.123456789Z ..."
            if (preg_match('/^(\d{4}-\d{2}-\d{2}T[^\s]+)\s+(.*)$/', $line, $m)) {
                $tsIso = $m[1];
                $msg = $m[2];

                $unix = strtotime($tsIso);
                if ($unix && $unix > $maxTs) $maxTs = $unix;

                $bus->push($server, $msg, 'log');
            } else {
                $bus->push($server, $line, 'log');
            }
        }

        // двигаем курсор на секунду вперёд, чтобы не дублировать границу
        if ($maxTs > 0) {
            $bus->setCursor($server, $maxTs + 1);
        }

        if ($debug) {
            $this->line("  - {$server->uuid} logs ok");
        }
    }
}