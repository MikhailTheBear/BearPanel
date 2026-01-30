<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8"
     x-data="consoleView()"
     x-init="init()"
     wire:poll.2s="refreshConsole"
>
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="text-sm text-gray-500">Server</div>
            <h1 class="text-2xl font-semibold">{{ $server->name }} — Console</h1>
            <div class="mt-1 text-sm text-gray-600">
                UUID: <span class="font-mono">{{ $server->uuid }}</span>
            </div>
        </div>

        <div class="text-right space-y-2">
            <div class="flex gap-2 justify-end">
                <button type="button" wire:click="start" class="px-3 py-2 rounded bg-gray-900 text-white">Start</button>
                <button type="button" wire:click="stop" class="px-3 py-2 rounded border">Stop</button>
                <button type="button" wire:click="restart" class="px-3 py-2 rounded border">Restart</button>
                <button type="button" wire:click="clear" class="px-3 py-2 rounded border">Clear</button>
            </div>

            <div class="text-sm text-gray-500">Status</div>
            <div class="inline-flex px-3 py-1 rounded bg-gray-100 text-sm">
                {{ $server->status }}
            </div>
        </div>
    </div>

    @include('servers._tabs', ['server' => $server])

    @error('runtime')
        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="bg-black rounded-lg shadow border overflow-hidden">
        <div class="flex items-center justify-between px-4 py-2 border-b border-white/10">
            <div class="text-xs text-white/60">Live console</div>

            <label class="text-xs text-white/60 flex items-center gap-2">
                <input type="checkbox" class="rounded" x-model="autoScroll">
                Auto-scroll
            </label>
        </div>

        <div class="h-[520px] overflow-auto font-mono text-xs leading-5 p-4 text-white"
             x-ref="box">
            <template x-for="(l, idx) in lines" :key="idx">
                <div class="whitespace-pre-wrap break-words">
                    <span class="text-white/40" x-text="formatTs(l.ts)"></span>
                    <span x-text="' '"></span>

                    <template x-if="l.type === 'sys'">
                        <span class="text-sky-300" x-text="l.line"></span>
                    </template>

                    <template x-if="l.type === 'cmd'">
                        <span class="text-amber-300" x-text="l.line"></span>
                    </template>

                    <template x-if="l.type === 'err'">
                        <span class="text-red-300" x-text="l.line"></span>
                    </template>

                    <template x-if="l.type === 'info'">
                        <span class="text-white" x-text="l.line"></span>
                    </template>
                </div>
            </template>
        </div>

        <div class="border-t border-white/10 p-3 bg-black">
            <form class="flex gap-2" x-on:submit.prevent="$wire.send()">
                <input type="text"
                       wire:model.defer="command"
                       class="flex-1 rounded bg-black text-white border border-white/20 px-3 py-2 font-mono text-sm"
                       placeholder="Type command... (help, op Steve, stop)"
                       x-on:keydown.enter.prevent="$wire.send()">

                <button type="button"
                        class="px-4 py-2 rounded bg-gray-900 text-white border border-white/10"
                        wire:click="send">
                    Send
                </button>
            </form>
        </div>
    </div>

    <script>
        function consoleView() {
            return {
                autoScroll: true,
                lines: @js($lines ?? []),

                init() {
                    window.addEventListener('console:replace', (e) => {
                        this.lines = e.detail.lines || [];
                        this.$nextTick(() => this.maybeScroll());
                    });

                    // first scroll
                    this.$nextTick(() => this.maybeScroll());
                },

                maybeScroll() {
                    if (!this.autoScroll) return;
                    const el = this.$refs.box;
                    if (!el) return;
                    el.scrollTop = el.scrollHeight;
                },

                formatTs(ts) {
                    try {
                        const d = new Date(ts);
                        return d.toLocaleTimeString();
                    } catch (e) {
                        return '';
                    }
                }
            }
        }
    </script>
</div>