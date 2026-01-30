<?php

namespace App\Livewire\Admin\Nodes;

use App\Models\Node;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';
    public string $fqdn = '';
    public string $scheme = 'http';
    public int $daemon_port = 8080;
    public ?string $token = null;
    public bool $is_public = false;
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'fqdn' => ['required', 'string', 'max:255'],
            'scheme' => ['required', 'in:http,https'],
            'daemon_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'token' => ['nullable', 'string', 'max:255'],
            'is_public' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $node = Node::findOrFail($id);

        $this->editingId = $node->id;
        $this->name = $node->name;
        $this->fqdn = $node->fqdn;
        $this->scheme = $node->scheme;
        $this->daemon_port = (int) $node->daemon_port;
        $this->token = $node->token;
        $this->is_public = (bool) $node->is_public;
        $this->is_active = (bool) $node->is_active;
    }

    public function save(): void
    {
        $data = $this->validate();

        // FQDN + port must be unique (friendly validation)
        $q = Node::query()
            ->where('fqdn', $data['fqdn'])
            ->where('daemon_port', $data['daemon_port']);

        if ($this->editingId) {
            $q->where('id', '!=', $this->editingId);
        }

        if ($q->exists()) {
            $this->addError('fqdn', 'This FQDN + port is already used.');
            return;
        }

        Node::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->resetForm();
        $this->editingId = null;

        session()->flash('status', 'Node saved.');
    }

    public function delete(int $id): void
    {
        Node::whereKey($id)->delete();
        session()->flash('status', 'Node deleted.');
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->fqdn = '';
        $this->scheme = 'http';
        $this->daemon_port = 8080;
        $this->token = null;
        $this->is_public = false;
        $this->is_active = true;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function render()
    {
        $nodes = Node::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('name', 'like', "%{$this->search}%")
                        ->orWhere('fqdn', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.nodes.index', compact('nodes'))
            ->layout('layouts.app');
    }
}
