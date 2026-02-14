<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">{{ __('Admin_Nodes') }}</h1>

        <div class="flex gap-2">
            <input
                type="text"
                wire:model.live="search"
                placeholder="{{ __('Search...') }}"
                class="rounded-md border-gray-300"
            />
            <button wire:click="create" class="px-3 py-2 rounded-md bg-gray-900 text-white">
                {{ __('New Node') }}
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
                            <th class="p-3">{{ __('Address') }}</th>
                            <th class="p-3">{{ __('Public') }}</th>
                            <th class="p-3">{{ __('Active') }}</th>
                            <th class="p-3 w-40"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($nodes as $node)
                            <tr>
                                <td class="p-3">{{ $node->name }}</td>
                                <td class="p-3">
                                    {{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemon_port }}
                                </td>
                                <td class="p-3">{{ $node->is_public ? __('Yes') : __('No')  }}</td>
                                <td class="p-3">{{ $node->is_active ?  __('Yes') :  __('No')  }}</td>
                                <td class="p-3 text-right">
                                    <button wire:click="edit({{ $node->id }})" class="text-blue-600">{{ __('Edit') }}</button>
                                    <button
                                        wire:click="delete({{ $node->id }})"
                                        class="text-red-600 ml-3"
                                        onclick="return confirm('Delete node?')"
                                    >
                                        {{ __('Delete') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="p-3 text-gray-500" colspan="5">{{ __('No nodes yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-3">
                    {{ $nodes->links() }}
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-5">
            <h2 class="text-lg font-semibold mb-4">
                {{ $editingId ?  __('Edit Node') :  __('Create Node') }}
            </h2>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-700">{{ __('Name') }}</label>
                    <input type="text" wire:model="name" class="w-full rounded-md border-gray-300">
                    @error('name') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-700">{{ __('FQDN / IP') }}</label>
                    <input type="text" wire:model="fqdn" class="w-full rounded-md border-gray-300" placeholder="node1.example.com">
                    @error('fqdn') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-gray-700">{{ __('Scheme') }}</label>
                        <select wire:model="scheme" class="w-full rounded-md border-gray-300">
                            <option value="http">http</option>
                            <option value="https">https</option>
                        </select>
                        @error('scheme') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700">{{ __('Daemon port') }}</label>
                        <input type="number" wire:model="daemon_port" class="w-full rounded-md border-gray-300">
                        @error('daemon_port') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-700">{{ __('Token (optional)') }}</label>
                    <input type="text" wire:model="token" class="w-full rounded-md border-gray-300">
                    @error('token') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="is_public" class="rounded border-gray-300">
                    <span class="text-sm text-gray-700">{{ __('Public') }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="is_active" class="rounded border-gray-300">
                    <span class="text-sm text-gray-700">{{ __('Active') }}</span>
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
