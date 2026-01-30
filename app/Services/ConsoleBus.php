<?php

namespace App\Services;

use App\Events\ServerLogLine;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;

class ConsoleBus
{
    private int $maxLines = 400;

    private function key(Server $server): string
    {
        return "console:buffer:{$server->uuid}";
    }

    private function keyCursor(Server $server): string
    {
        return "console:cursor:{$server->uuid}";
    }

    /** @return array<int, array{ts:string,type:string,line:string}> */
    public function getBuffer(Server $server): array
    {
        return Cache::get($this->key($server), []);
    }

    public function clear(Server $server): void
    {
        Cache::forget($this->key($server));
        Cache::forget($this->keyCursor($server));
    }

    public function push(Server $server, string $line, string $type = 'log'): void
    {
        $ts = now()->format('H:i:s');

        $item = [
            'ts' => $ts,
            'type' => $type,
            'line' => $line,
        ];

        $buf = $this->getBuffer($server);
        $buf[] = $item;

        if (count($buf) > $this->maxLines) {
            $buf = array_slice($buf, -$this->maxLines);
        }

        Cache::put($this->key($server), $buf, now()->addHours(6));

        // broadcast “как в pterodactyl”
        broadcast(new ServerLogLine($server->uuid, "[{$ts}] {$line}"))->toOthers();
        broadcast(new ServerLogLine($server->uuid, "[{$ts}] {$line}"));
    }

    public function getCursor(Server $server): int
    {
        return (int) Cache::get($this->keyCursor($server), 0);
    }

    public function setCursor(Server $server, int $unixTs): void
    {
        Cache::put($this->keyCursor($server), $unixTs, now()->addHours(6));
    }
}