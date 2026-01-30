<?php

namespace App\Livewire\Admin\Servers;

use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;

    public string $name = '';
    public string $owner_email = '';
    public int $node_id = 0;
    public string $status = 'stopped';

    public ?int $allocation_port = null;

    public ?int $limit_cpu = null;
    public ?int $limit_ram = null;
    public ?int $limit_disk = null;

    protected function rules(): array
    {
        $uniquePort = 'unique:servers,allocation_port';
        if ($this->editingId) {
            $uniquePort .= ',' . $this->editingId;
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'exists:users,email'],
            'node_id' => ['required', 'integer', 'exists:nodes,id'],
            'status' => ['required', 'in:installing,running,stopped,suspended'],

            // ✅ фиксированный порт (можно оставить null -> тогда агент сам выделит)
            'allocation_port' => ['nullable', 'integer', 'min:1024', 'max:65535', $uniquePort],

            'limit_cpu' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'limit_ram' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'limit_disk' => ['nullable', 'integer', 'min:1', 'max:10000000'],
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
        $server = Server::with(['owner', 'node'])->findOrFail($id);

        $this->editingId = $server->id;
        $this->name = $server->name;
        $this->owner_email = $server->owner?->email ?? '';
        $this->node_id = (int) $server->node_id;
        $this->status = $server->status ?? 'stopped';

        $this->allocation_port = $server->allocation_port ? (int) $server->allocation_port : null;

        $l = $server->limits ?? [];
        $this->limit_cpu = $l['cpu'] ?? null;
        $this->limit_ram = $l['ram'] ?? null;
        $this->limit_disk = $l['disk'] ?? null;
    }

    public function save(): void
    {
        $data = $this->validate();

        $owner = User::where('email', $data['owner_email'])->firstOrFail();

        $limits = [
            'cpu' => $this->limit_cpu,
            'ram' => $this->limit_ram,
            'disk' => $this->limit_disk,
        ];
        $limits = array_filter($limits, fn($v) => $v !== null);

        if ($this->editingId) {
            // UPDATE
            $server = Server::findOrFail($this->editingId);

            $portChanged = (int)($server->allocation_port ?? 0) !== (int)($data['allocation_port'] ?? 0);

            $server->update([
                'name' => $data['name'],
                'owner_id' => $owner->id,
                'node_id' => $data['node_id'],
                'status' => $data['status'],
                'allocation_port' => $data['allocation_port'] ?? null,
                'limits' => $limits ?: null,
            ]);

            // ✅ если порт поменяли — пересоздадим контейнер (проще и надёжнее)
            // агент сам создаст новый с новым портом
            if ($portChanged) {
                $server->update([
                    'container_id' => null,
                    'data_path' => $server->data_path ?: storage_path('app/servers/' . $server->uuid),
                ]);
            }
        } else {
            // CREATE (uuid must exist on insert)
            Server::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'owner_id' => $owner->id,
                'node_id' => $data['node_id'],
                'status' => $data['status'],
                'allocation_port' => $data['allocation_port'] ?? null,
                'limits' => $limits ?: null,
            ]);
        }

        $this->resetForm();
        $this->editingId = null;

        session()->flash('status', 'Server saved.');
    }

    public function delete(int $id): void
    {
        Server::whereKey($id)->delete();
        session()->flash('status', 'Server deleted.');
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->owner_email = '';
        $this->node_id = 0;
        $this->status = 'stopped';

        $this->allocation_port = null;

        $this->limit_cpu = null;
        $this->limit_ram = null;
        $this->limit_disk = null;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function render()
    {
        $nodes = Node::query()->orderBy('name')->get();

        $servers = Server::query()
            ->with(['owner', 'node'])
            ->when($this->search !== '', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhereHas('owner', fn($qq) => $qq->where('email', 'like', "%{$this->search}%"));
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.servers.index', compact('servers', 'nodes'))
            ->layout('layouts.app');
    }
}
