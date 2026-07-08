<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Services\ConsoleBus;
use App\Services\DockerRuntime;
use Livewire\Component;

class Console extends Component
{
    public Server $server;

    public array $lines = [];
    public string $command = '';

    public bool $reverbOnline = true;

    public function mount(Server $server, ConsoleBus $bus): void
    {
        abort_if(!$server->canAccess(auth()->user()), 403);

        $this->server = $server->load('node', 'owner');
        $this->lines = $bus->getBuffer($this->server);

        $this->checkReverb();
        $this->dispatch('console:replace', lines: $this->lines);
    }

    public function pollHeartbeat(): void
    {
        $this->checkReverb();
    }

    protected function checkReverb(): void
    {
        $host = parse_url(config('broadcasting.connections.reverb.host') ?? 'http://127.0.0.1', PHP_URL_HOST) ?? '127.0.0.1';
        $port = env('REVERB_PORT', 8080); // прямое использование env()

        try {
            $conn = @fsockopen($host, $port, $errno, $errstr, 1);
            $wasOnline = $this->reverbOnline;
            $this->reverbOnline = $conn !== false;
            if ($conn) fclose($conn);
            if ($this->reverbOnline && !$wasOnline) {
                
            $this->dispatch('reverb:status', status: 'connected', message: 'Reverb подключён');
            } elseif (!$this->reverbOnline && $wasOnline) {
            $this->dispatch('reverb:status', status: 'disconnected', message: 'Reverb отключён');
            }
        } catch (\Throwable) {
            $this->reverbOnline = false;
            $this->dispatch('reverb:status', status: 'error', message: 'Ошибка подключения к Reverb');
        }
    }

    public function refreshConsole(ConsoleBus $bus): void
    {
        $this->server->refresh();
        $this->lines = $bus->getBuffer($this->server);
        $this->dispatch('console:replace', lines: $this->lines);
    }

    protected function guardReverb(): bool
    {
        if (!$this->reverbOnline) {
            $this->addError('runtime', __('Reverb is not running.'));
            return false;
        }
        return true;
    }

    public function start(DockerRuntime $rt, ConsoleBus $bus): void
    {
        if (!$this->guardReverb()) return;

        $bus->push($this->server, __("BearPanel: Starting server..."), 'sys');

        try {
            $rt->start($this->server);
        } catch (\Throwable $e) {
            $this->addError('runtime', $e->getMessage());
            $this->dispatch('console:error', message: $e->getMessage(), trace: $e->getTraceAsString());
        }

        $this->refreshConsole($bus);
    }

    public function stop(DockerRuntime $rt, ConsoleBus $bus): void
    {
        if (!$this->guardReverb()) return;

        try {
            $rt->stop($this->server);
        } catch (\Throwable $e) {
            $this->addError('runtime', $e->getMessage());
        }

        $this->refreshConsole($bus);
    }

    public function restart(DockerRuntime $rt, ConsoleBus $bus): void
    {
        if (!$this->guardReverb()) return;

        try {
            $rt->restart($this->server);
        } catch (\Throwable $e) {
            $this->addError('runtime', $e->getMessage());
        }

        $this->refreshConsole($bus);
    }

    public function send(ConsoleBus $bus, DockerRuntime $rt): void
    {
        if (!$this->guardReverb()) return;

        $cmd = trim($this->command);
        if ($cmd === '') return;

        try {
            $rt->sendCommand($this->server, $cmd);
        } catch (\Throwable $e) {
            $this->addError('runtime', $e->getMessage());
        }

        $this->command = '';
        $this->refreshConsole($bus);
    }

    public function clear(ConsoleBus $bus): void
    {
        $bus->clear($this->server);
        $this->lines = [];
        $this->dispatch('console:replace', lines: []);
    }

    public function render()
    {
        return view('livewire.servers.console')
            ->layout('layouts.app');
    }
}