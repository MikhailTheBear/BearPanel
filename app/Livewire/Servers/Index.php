<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $servers = Server::query()
            ->with(['node', 'owner'])
            ->when(!$user?->is_admin, fn ($q) => $q->where('owner_id', $user->id))
            ->when($this->search !== '', function ($q) {
                $s = $this->search;
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")
                       ->orWhere('uuid', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.servers.index', compact('servers'))
            ->layout('layouts.app');
    }
}