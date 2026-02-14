<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">{{ __('Admin_Servers') }}</h1>

        <div class="flex gap-2">
            <input
                type="text"
                wire:model.debounce.400ms="search"
                placeholder="{{ __('Search server or owner email...') }}"
                class="rounded-md border-gray-300"
            />
            <button wire:click="create" class="px-3 py-2 rounded-md bg-gray-900 text-white">
                {{ __('New Server') }}
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-sm text-gray-600">
                            <th class="p-3">{{ __('Name') }}</th>
                            <th class="p-3">{{ __('Owner') }}</th>
                            <th class="p-3">{{ __('Node') }}</th>
                            <th class="p-3">{{ __('Status') }}</th>
                            <th class="p-3">{{ __('Port') }}</th>
                            <th class="p-3 w-40"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($servers as $server)
                            <tr>
                                <td class="p-3 font-medium">{{ $server->name }}</td>
                                <td class="p-3 text-sm text-gray-700">{{ $server->owner?->email ?? '—' }}</td>
                                <td class="p-3 text-sm text-gray-700">{{ $server->node?->name ?? '—' }}</td>
                                <td class="p-3">
                                    <span class="inline-flex px-2 py-1 rounded text-xs bg-gray-100">
                                        {{ __('server.status.' . ($server->status ?? 'unknown')) }}
                                    </span>
                                </td>
                                <td class="p-3 text-sm font-mono text-gray-800">
                                    {{ $server->allocation_port ?? __('auto') }}
                                </td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('admin.servers.show', $server) }}" class="text-gray-900">
                                        {{ __('Open') }}
                                    </a>

                                    <button wire:click="edit({{ $server->id }})" class="text-blue-600 ml-3">{{ __('Edit') }}</button>

                                    <button wire:click="delete({{ $server->id }})"
                                            class="text-red-600 ml-3"
                                            onclick="return confirm('{{ __('Delete server?') }}')">
                                        {{ __('Delete') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="p-3 text-gray-500" colspan="6">{{ __('No servers yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-3">
                    {{ $servers->links() }}
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-5">
            <h2 class="text-lg font-semibold mb-4">
                {{ $editingId ? __('Edit Server') : __('Create Server') }}
            </h2>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-700">{{ __('Name') }}</label>
                    <input type="text" wire:model="name" class="w-full rounded-md border-gray-300">
                    @error('name') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-700">{{ __('Owner email') }}</label>
                    <input type="text" wire:model="owner_email" class="w-full rounded-md border-gray-300" placeholder="user@example.com">
                    @error('owner_email') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-700">{{ __('Node') }}</label>
                    <select wire:model="node_id" class="w-full rounded-md border-gray-300">
                        <option value="0">{{ __('— select node —') }}</option>
                        @foreach($nodes as $n)
                            <option value="{{ $n->id }}">{{ $n->name }} ({{ $n->fqdn }}:{{ $n->daemon_port }})</option>
                        @endforeach
                    </select>
                    @error('node_id') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-700">{{ __('Status') }}</label>
                    <select wire:model="status" class="w-full rounded-md border-gray-300">
                        <option value="running">{{ __('Running') }}</option>
                        <option value="stopped">{{ __('Stopped') }}</option>
                    </select>
                    @error('status') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                {{-- ✅ FIXED PORT --}}
                <div>
                    <label class="block text-sm text-gray-700">{{ __('Port') }}</label>
                    <input type="number"
                           wire:model="allocation_port"
                           class="w-full rounded-md border-gray-300"
                           placeholder="{{ __('e.g. 25566 (leave empty = auto)') }}">
                    @error('allocation_port') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    <div class="text-xs text-gray-500 mt-1">
                        {{ __('Leave empty to auto-allocate. If you set a port, it will stay fixed.') }}
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm text-gray-700">{{ __('CPU') }}</label>
                        <input type="number" wire:model="limit_cpu" class="w-full rounded-md border-gray-300" placeholder="{{ __('e.g.') }} 100">
                        @error('limit_cpu') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">{{ __('RAM (MB)') }}</label>
                        <input type="number" wire:model="limit_ram" class="w-full rounded-md border-gray-300" placeholder="{{ __('e.g.') }} 1024">
                        @error('limit_ram') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">{{ __('Disk (MB)') }}</label>
                        <input type="number" wire:model="limit_disk" class="w-full rounded-md border-gray-300" placeholder="{{ __('e.g.') }} 10000">
                        @error('limit_disk') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="pt-2 flex gap-2">
                    <button wire:click="save" class="px-3 py-2 rounded-md bg-gray-900 text-white">
                        {{ __('Save') }}
                    </button>
                    @if($editingId)
                        <button wire:click="create" class="px-3 py-2 rounded-md border">
                            {{ __('Cancel') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
