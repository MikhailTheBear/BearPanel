<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>

            <a href="{{ route('servers.index') }}" class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm">
               {{ __('My Servers') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Overview cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow rounded-lg p-5">
                    <div class="text-sm text-gray-500">{{ __('Account') }}</div>
                    <div class="mt-2 font-semibold text-lg text-gray-900">
                        {{ auth()->user()->name }}
                    </div>
                    <div class="text-sm text-gray-600">{{ auth()->user()->email }}</div>
                    <div class="mt-3 text-xs text-gray-500">
                        {{ __('User ID') }}: <span class="font-mono">{{ auth()->id() }}</span>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-5">
                    <div class="text-sm text-gray-500">{{ __('Role') }}</div>
                    <div class="mt-2 font-semibold text-lg">
                        @if(auth()->user()->is_admin ?? false)
                            {{ __('Admin') }}
                        @else
                            {{ __('User') }}
                        @endif
                    </div>

                    <div class="mt-3 text-xs text-gray-500">
                        {{ __('Registered') }}:
                        <span class="font-mono">{{ optional(auth()->user()->created_at)->format('Y-m-d H:i') }}</span>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-5">
                    <div class="text-sm text-gray-500">{{ __('Quick links') }}</div>

                    <div class="mt-3 space-y-2 text-sm">
                        <a class="block underline" href="{{ route('servers.index') }}">{{ __('Servers') }}</a>

                        @if(auth()->user()->is_admin ?? false)
                            <a class="block underline" href="{{ route('admin.servers.index') }}">{{ __('Admin_Servers') }}</a>
                            <a class="block underline" href="{{ route('admin.nodes.index') }}">{{ __('Admin_Nodes') }}</a>
                        @endif

                        <a class="block underline" href="{{ route('profile.show') }}">{{ __('Profile') }}</a>
                    </div>
                </div>
            </div>

            {{-- Recent servers --}}
            <div class="bg-white shadow rounded-lg p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('Recent servers') }}</h3>
                    <a class="text-sm underline" href="{{ route('servers.index') }}">{{ __('Open all') }}</a>
                </div>

                @php
                    $servers = \App\Models\Server::query()
                        ->when(!(auth()->user()->is_admin ?? false), fn($q) => $q->where('owner_id', auth()->id()))
                        ->orderByDesc('updated_at')
                        ->limit(8)
                        ->get();
                @endphp

                @if($servers->isEmpty())
                    <div class="mt-4 text-sm text-gray-500">
                        {{ __('No servers yet.') }}
                    </div>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-gray-500">
                                <tr class="border-b">
                                    <th class="py-2 text-left font-medium">{{ __('Name') }}</th>
                                    <th class="py-2 text-left font-medium">{{ __('Status') }}</th>
                                    <th class="py-2 text-left font-medium">{{ __('Host port') }}</th>
                                    <th class="py-2 text-left font-medium">{{ __('Updated') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($servers as $s)
                                    <tr>
                                        <td class="py-2">
                                            <a class="font-medium underline" href="{{ route('servers.show', $s) }}">
                                                {{ $s->name }}
                                            </a>
                                            <div class="text-xs text-gray-500 font-mono">{{ $s->uuid }}</div>
                                        </td>
                                        <td class="py-2">
                                            <span class="inline-flex px-2 py-1 rounded bg-gray-100">
                                                {{ __('server.status.' . ($s->status ?? 'unknown')) }}
                                            </span>
                                        </td>
                                        <td class="py-2 font-mono">
                                            {{ $s->host_port ?? '—' }}
                                        </td>
                                        <td class="py-2 text-gray-600">
                                            {{ optional($s->updated_at)->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>