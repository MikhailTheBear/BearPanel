<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Node;
use App\Models\Server;

class Overview extends Component
{
    public function render()
    {
        return view('livewire.admin.overview', [
            'usersCount'   => User::count(),
            'nodesCount'   => Node::count(),
            'serversCount' => Server::count(),

            // Panel info
            'panelName'    => env('APP_NAME', 'BearPanel'),
            'panelVersion' => env('PANEL_VERSION', '1.0'),

            // System info
            'phpVersion'   => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'env'          => app()->environment(),
        ])->layout('layouts.app');
    }
}