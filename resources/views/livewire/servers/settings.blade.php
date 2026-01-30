<div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="text-sm text-gray-500">Server</div>
            <h1 class="text-2xl font-semibold">{{ $server->name }} — Settings</h1>
            <div class="mt-1 text-sm text-gray-600">
                UUID: <span class="font-mono">{{ $server->uuid }}</span>
            </div>
        </div>

        <div class="text-right">
            <a class="text-sm underline" href="{{ route('servers.show', $server) }}">Back</a>
        </div>
    </div>

    @include('servers._tabs', ['server' => $server])

    @if (session('status'))
        <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @error('startup_command')
        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="bg-white shadow rounded-lg p-6 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-500">Container port (allocation_port)</div>
                <div class="font-mono text-sm">{{ $server->allocation_port ?? '—' }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-500">Host port (fixed, read-only)</div>
                <div class="font-mono text-sm">{{ $server->host_port ?? '—' }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    Host port нельзя менять в Settings (чтобы не было рассинхрона).
                </div>
            </div>
        </div>

        <hr>

        <div>
            <label class="block text-sm text-gray-700">Jar file name</label>
            <input type="text" wire:model.defer="jar_file" class="w-full rounded border-gray-300">
            @error('jar_file') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            <div class="text-xs text-gray-500 mt-1">Файл должен лежать в корне Files.</div>
        </div>

        <div>
            <label class="block text-sm text-gray-700">Java version</label>
            <select wire:model.defer="java_version" class="w-full rounded border-gray-300">
                <option value="17">17</option>
                <option value="21">21</option>
            </select>
            @error('java_version') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-sm text-gray-700">Startup command template</label>
            <textarea
                wire:model.defer="startup_command"
                class="w-full rounded border-gray-300 font-mono text-sm"
                rows="5"
                placeholder="java -Xms256M -Xmx@{{RAM}}M -jar @{{JAR}} nogui"
            ></textarea>

            <div class="mt-2 text-xs text-gray-500">
                Allowed variables:
                <span class="font-mono">
                    @{{RAM}} @{{JAR}} @{{UUID}} @{{SERVER_NAME}}
                </span>
            </div>

            <div class="mt-2 text-xs text-gray-500">
                ⚠️ Нельзя хардкодить <span class="font-mono">-Xmx/-Xms</span> числами — только через
                <span class="font-mono">@{{RAM}}</span>.
            </div>
        </div>

        <div class="pt-2 flex gap-2">
            <button wire:click="save" class="px-3 py-2 rounded bg-gray-900 text-white">
                Save
            </button>

            <a href="{{ route('servers.files', $server) }}" class="px-3 py-2 rounded border">
                Go to Files
            </a>
        </div>
    </div>
</div>