<?php

namespace App\Livewire\Admin\Servers;

use App\Models\Server;
use Livewire\Component;



class Show extends Component
{
    public Server $server;

    public function mount(Server $server): void
    {
        $this->server = $server->load('owner', 'node');
    }

    public function refreshServer(): void
{
    $this->server->refresh()->load('node', 'owner');
}

    public function render()
    {
        return view('livewire.admin.servers.show')
            ->layout('layouts.app');
    }
}