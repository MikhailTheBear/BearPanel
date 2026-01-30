<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Services\DockerRuntime;
use Livewire\Component;

class Show extends Component
{
    public Server $server;

    public function mount(Server $server): void
    {
        abort_if(!$server->canAccess(auth()->user()), 403);

        $this->server = $server->load('node', 'owner');
    }

    public function refreshServer(): void
    {
        $this->server->refresh();
        $this->server->load('node', 'owner');
    }

    public function start(DockerRuntime $runtime): void
    {
        $this->authorizeOwner();
        try {
            $runtime->start($this->server);
            session()->flash('status', 'Server started.');
        } catch (\Throwable $e) {
            $this->addError('runtime', $e->getMessage());
        }
        $this->refreshServer();
    }

    public function stop(DockerRuntime $runtime): void
    {
        $this->authorizeOwner();
        try {
            $runtime->stop($this->server);
            session()->flash('status', 'Server stopped.');
        } catch (\Throwable $e) {
            $this->addError('runtime', $e->getMessage());
        }
        $this->refreshServer();
    }

    public function restart(DockerRuntime $runtime): void
    {
        $this->authorizeOwner();
        try {
            $runtime->restart($this->server);
            session()->flash('status', 'Server restarted.');
        } catch (\Throwable $e) {
            $this->addError('runtime', $e->getMessage());
        }
        $this->refreshServer();
    }

    public function recreate(DockerRuntime $runtime): void
    {
        $this->authorizeOwner();
        try {
            $runtime->recreate($this->server);
            session()->flash('status', 'Container recreated.');
        } catch (\Throwable $e) {
            $this->addError('runtime', $e->getMessage());
        }
        $this->refreshServer();
    }

    private function authorizeOwner(): void
    {
        if (!$this->server->canAccess(auth()->user())) abort(403);
    }

    public function render()
    {
        return view('livewire.servers.show')->layout('layouts.app');
    }
}