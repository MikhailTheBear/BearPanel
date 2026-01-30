<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8" wire:poll.2s="refreshServer">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="text-sm text-gray-500">Server</div>
            <h1 class="text-2xl font-semibold">{{ $server->name }}</h1>
            <div class="mt-1 text-sm text-gray-600">
                UUID: <span class="font-mono">{{ $server->uuid }}</span>
            </div>
        </div>

        <div class="text-right space-y-2">
            <div class="flex gap-2 justify-end">
                <button wire:click="start" class="px-3 py-2 rounded bg-gray-900 text-white">Start</button>
                <button wire:click="stop" class="px-3 py-2 rounded border">Stop</button>
                <button wire:click="restart" class="px-3 py-2 rounded border">Restart</button>
                <button wire:click="recreate" class="px-3 py-2 rounded border"
                        onclick="return confirm('Recreate container? It will delete old container and create new one.')">
                    Recreate
                </button>
            </div>

            <div class="text-sm text-gray-500">Status</div>
            <div class="inline-flex px-3 py-1 rounded bg-gray-100 text-sm">
                {{ $server->status }}
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @error('runtime')
        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800">
            {{ $message }}
        </div>
    @enderror

    @include('servers._tabs', ['server' => $server])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                        <div class="text-gray-500">Address</div>
                        <div class="font-medium font-mono">
                            127.0.0.1:{{ $server->host_port ?? '—' }}
                        </div>
                        <div class="text-gray-600 text-xs">Fixed host port</div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-5">
                <h2 class="text-lg font-semibold mb-3">Startup</h2>

                <div class="text-sm text-gray-700">
                    <div>Java: <span class="font-mono">{{ $server->java_version ?? '21' }}</span></div>
                    <div>Jar: <span class="font-mono">{{ $server->jar_file ?? 'server.jar' }}</span></div>
                    <div class="mt-2 text-xs text-gray-500">Template:</div>
                    <pre class="mt-1 p-3 rounded bg-gray-50 border text-xs overflow-auto">{{ $server->startup_command ?? '—' }}</pre>
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

        <div class="bg-white shadow rounded-lg p-5">
            <h2 class="text-lg font-semibold mb-3">Quick links</h2>
            <div class="space-y-2 text-sm">
                <a class="block text-gray-900 underline" href="{{ route('servers.console', $server) }}">Console</a>
                <a class="block text-gray-900 underline" href="{{ route('servers.files', $server) }}">Files</a>
                <a class="block text-gray-900 underline" href="{{ route('servers.settings', $server) }}">Settings</a>
            </div>

            <div class="mt-4 text-xs text-gray-500">
                If port already allocated — stop old container or change host_port, then Recreate.
            </div>
        </div>
    </div>
</div>