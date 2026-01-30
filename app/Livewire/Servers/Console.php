<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Services\ConsoleBus;
use App\Services\DockerRuntime;
use Livewire\Component;

class Console extends Component
{
    public Server $server;

    /** @var array<int, array{ts:string,type:string,line:string}> */
    public array $lines = [];

    public string $command = '';

    public function mount(Server $server, ConsoleBus $bus): void
    {
        abort_if(!$server->canAccess(auth()->user()), 403);

        $this->server = $server->load('node', 'owner');
        $this->lines = $bus->getBuffer($this->server);

        $this->dispatch('console:replace', lines: $this->lines);
    }

    public function refreshConsole(ConsoleBus $bus): void
    {
        $this->server->refresh();
        $this->lines = $bus->getBuffer($this->server);
        $this->dispatch('console:replace', lines: $this->lines);
    }

    public function start(DockerRuntime $rt, ConsoleBus $bus): void
    {
        $this->server->refresh();
        $bus->push($this->server, "BearPanel: The server is in the process of starting", 'sys');

        try {
            $rt->start($this->server);
            $bus->push($this->server, "BearPanel: Server marked as running...", 'sys');
        } catch (\Throwable $e) {
            $bus->push($this->server, "BearPanel: ERROR: " . $e->getMessage(), 'err');
            $this->addError('runtime', $e->getMessage());
        }

        $this->refreshConsole($bus);
    }

    public function stop(DockerRuntime $rt, ConsoleBus $bus): void
    {
        $this->server->refresh();
        $bus->push($this->server, "BearPanel: Stopping server...", 'sys');

        try {
            $rt->stop($this->server);
            $bus->push($this->server, "BearPanel: Server stopped.", 'sys');
        } catch (\Throwable $e) {
            $bus->push($this->server, "BearPanel: ERROR: " . $e->getMessage(), 'err');
            $this->addError('runtime', $e->getMessage());
        }

        $this->refreshConsole($bus);
    }

    public function restart(DockerRuntime $rt, ConsoleBus $bus): void
    {
        $this->server->refresh();
        $bus->push($this->server, "BearPanel: Restarting server...", 'sys');

        try {
            $rt->restart($this->server);
            $bus->push($this->server, "BearPanel: Server marked as running...", 'sys');
        } catch (\Throwable $e) {
            $bus->push($this->server, "BearPanel: ERROR: " . $e->getMessage(), 'err');
            $this->addError('runtime', $e->getMessage());
        }

        $this->refreshConsole($bus);
    }

    public function send(ConsoleBus $bus, DockerRuntime $rt): void
    {
        $this->server->refresh();

        $cmd = trim($this->command);
        if ($cmd === '') return;

        $bus->push($this->server, "BearPanel: {$cmd}", 'cmd');

        try {
            $rt->sendCommand($this->server, $cmd);
        } catch (\Throwable $e) {
            $bus->push($this->server, "BearPanel: ERROR: " . $e->getMessage(), 'err');
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
        return view('livewire.servers.console')->layout('layouts.app');
    }
}