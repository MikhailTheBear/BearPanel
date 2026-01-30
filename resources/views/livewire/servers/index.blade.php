<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">My Servers</h1>
        <div class="text-sm text-gray-500">Servers assigned to your account</div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr class="text-left text-sm text-gray-600">
                    <th class="p-3">Name</th>
                    <th class="p-3">Node</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Limits</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($servers as $server)
                    <tr>
                        <td class="p-3 font-medium">
                            <a href="{{ route('servers.show', $server) }}" class="text-blue-600 hover:underline">
                                {{ $server->name }}
                            </a>
                            <div class="text-xs text-gray-500 font-mono">{{ $server->uuid }}</div>
                        </td>
                        <td class="p-3 text-gray-700">
                            {{ $server->node?->name ?? '—' }}
                        </td>
                        <td class="p-3">
                            <span class="inline-flex px-2 py-1 rounded text-xs bg-gray-100">
                                {{ $server->status }}
                            </span>
                        </td>
                        <td class="p-3 text-sm text-gray-700">
                            @php($l = $server->limits ?? [])
                            CPU: {{ $l['cpu'] ?? '—' }} |
                            RAM: {{ $l['ram'] ?? '—' }} MB |
                            Disk: {{ $l['disk'] ?? '—' }} MB
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-3 text-gray-500" colspan="4">No servers yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-3">
            {{ $servers->links() }}
        </div>
    </div>
</div>
