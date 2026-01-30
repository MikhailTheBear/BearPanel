<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8" wire:poll.2s="refreshServer">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="text-sm text-gray-500">Server</div>
            <h1 class="text-2xl font-semibold">{{ $server->name }}</h1>
            <div class="mt-1 text-sm text-gray-600">
                UUID: <span class="font-mono">{{ $server->uuid }}</span>
            </div>
        </div>

        <div class="text-right">
            <div class="flex gap-2 justify-end">
                <button wire:click="start" class="px-3 py-2 rounded bg-gray-900 text-white">Start</button>
                <button wire:click="stop" class="px-3 py-2 rounded border">Stop</button>
                <button wire:click="restart" class="px-3 py-2 rounded border">Restart</button>
            </div>

            <div class="mt-2 text-sm text-gray-500">Status</div>
            <div class="inline-flex px-3 py-1 rounded bg-gray-100 text-sm">
                {{ $server->status }}
            </div>
        </div>
    </div>

    @include('servers._tabs', ['server' => $server])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Overview -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow rounded-lg p-5">
                <h2 class="text-lg font-semibold mb-3">Overview</h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Node</div>
                        <div class="font-medium">{{ $server->node?->name ?? '—' }}</div>
                        <div class="text-gray-600">
                            {{ $server->node ? ($server->node->scheme.'://'.$server->node->fqdn.':'.$server->node->daemon_port) : '' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500">Owner</div>
                        <div class="font-medium">{{ $server->owner?->email ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Connect</div>
                        <div class="font-medium font-mono">
                            127.0.0.1:{{ $server->host_port ?? '—' }}
                        </div>
                        <div class="text-gray-600 text-xs">
                            Host port (fixed)
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-5">
                <h2 class="text-lg font-semibold mb-3">Limits</h2>

                @php($l = $server->limits ?? [])
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="p-3 rounded border">
                        <div class="text-gray-500">CPU</div>
                        <div class="font-semibold">{{ $l['cpu'] ?? '—' }}</div>
                    </div>
                    <div class="p-3 rounded border">
                        <div class="text-gray-500">RAM (MB)</div>
                        <div class="font-semibold">{{ $l['ram'] ?? '—' }}</div>
                    </div>
                    <div class="p-3 rounded border">
                        <div class="text-gray-500">Disk (MB)</div>
                        <div class="font-semibold">{{ $l['disk'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Runtime -->
        <div class="bg-white shadow rounded-lg p-5">
            <h2 class="text-lg font-semibold mb-3">Runtime</h2>

            <div class="space-y-3 text-sm">
                <div>
                    <div class="text-gray-500">Internal port</div>
                    <div class="font-mono">{{ $server->allocation_port ?? '—' }}</div>
                </div>

                <div>
                    <div class="text-gray-500">Container</div>
                    <div class="font-mono break-all">
                        {{ $server->container_id ? \Illuminate\Support\Str::limit($server->container_id, 16, '…') : '—' }}
                    </div>
                </div>

                <!-- <div>
                    <div class="text-gray-500">Data path</div>
                    <div class="font-mono break-all text-xs">
                        {{ $server->data_path ?? '—' }}
                    </div>
                </div> -->

                <div class="pt-2">
                    <button wire:click="recreate" class="w-full px-3 py-2 rounded-md border">
                        Recreate container
                    </button>
                    <div class="mt-2 text-xs text-gray-500">
                        If container is broken or image changed.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>