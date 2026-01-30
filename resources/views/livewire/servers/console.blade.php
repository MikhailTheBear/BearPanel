<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="flex items-start justify-between gap-4 mb-2">
        <div>
            <div class="text-sm text-gray-500">Server</div>
            <h1 class="text-2xl font-semibold">{{ $server->name }}</h1>
            <div class="mt-1 text-sm text-gray-600">
                UUID: <span class="font-mono">{{ $server->uuid }}</span>
            </div>
        </div>

        <div class="text-right">
            <div class="text-sm text-gray-500">Status</div>
            <div class="inline-flex px-3 py-1 rounded bg-gray-100 text-sm"
                 wire:poll.2s="refreshConsole">
                {{ $server->status }}
            </div>

            @php
                $connectHost = $server->connectHost();
                $connectPort = $server->connectPort();
            @endphp

            <div class="mt-2 text-sm text-gray-500">Connect</div>
            <div class="inline-flex px-3 py-1 rounded bg-gray-100 text-sm font-mono">
                {{ $connectHost }}:{{ $connectPort ?? '—' }}
            </div>
        </div>
    </div>

    @include('servers._tabs', ['server' => $server])

    @error('runtime')
        <div class="my-3 p-3 rounded bg-red-50 border border-red-200 text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="mb-4 flex gap-2">
        <button wire:click="start" class="px-3 py-2 rounded-md bg-gray-900 text-white">Start</button>
        <button wire:click="stop" class="px-3 py-2 rounded-md border">Stop</button>
        <button wire:click="restart" class="px-3 py-2 rounded-md border">Restart</button>
        <button wire:click="clear" class="px-3 py-2 rounded-md border">Clear</button>
    </div>

    <div class="bg-white shadow rounded-lg p-5">
        <h2 class="text-lg font-semibold mb-3">Console</h2>

        <div id="server-console"
             wire:ignore
             class="rounded-lg border bg-gray-900 text-gray-100 font-mono text-sm p-4 h-72 overflow-auto">
            <div class="opacity-70">Connecting…</div>
        </div>

        <div class="mt-4 flex gap-2">
            <input
                class="flex-1 rounded-md border-gray-300"
                placeholder="type command..."
                wire:model.defer="command"
                wire:keydown.enter="send"
            >
            <button wire:click="send" class="px-3 py-2 rounded-md bg-gray-900 text-white">
                Send
            </button>
        </div>

        <div class="mt-2 text-xs text-gray-500">
            Real-time logs should be pushed through Reverb (private channel).
        </div>
    </div>

    @once
        <script>
            (function () {
                function replace(lines) {
                    const box = document.getElementById('server-console');
                    if (!box) return;

                    box.innerHTML = '';
                    for (const row of (lines || [])) {
                        const div = document.createElement('div');
                        div.textContent = row.line;
                        box.appendChild(div);
                    }
                    box.scrollTop = box.scrollHeight;
                }

                function boot() {
                    const uuid = @json($server->uuid);

                    if (!window.Echo) return;

                    window.__bearpanelSubs = window.__bearpanelSubs || {};
                    if (window.__bearpanelSubs[uuid]) return;
                    window.__bearpanelSubs[uuid] = true;

                    window.Echo.private(`servers.${uuid}`)
                        .listen('.server.log', (e) => {
                            const box = document.getElementById('server-console');
                            if (!box) return;

                            const div = document.createElement('div');
                            div.textContent = e.line;
                            box.appendChild(div);
                            box.scrollTop = box.scrollHeight;
                        });
                }

                window.addEventListener('console:replace', (e) => replace(e.detail.lines));

                document.addEventListener('livewire:navigated', boot);
                document.addEventListener('DOMContentLoaded', boot);
            })();
        </script>
    @endonce
</div>