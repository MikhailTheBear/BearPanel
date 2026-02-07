<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Admin overview
            </h2>

            <div class="text-sm text-gray-500">
                BearPanel v{{ $panelVersion }}
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                {{-- Stats --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white shadow rounded-lg p-5">
                        <div class="text-sm text-gray-500">Users</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ $usersCount }}
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg p-5">
                        <div class="text-sm text-gray-500">Nodes</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ $nodesCount }}
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg p-5">
                        <div class="text-sm text-gray-500">Servers</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ $serversCount }}
                        </div>
                    </div>
                </div>

                {{-- Panel & System --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white shadow rounded-lg p-5">
                        <h3 class="text-lg font-semibold mb-4">
                            Panel
                        </h3>

                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-500">Name:</span>
                                <span class="font-medium">{{ $panelName }}</span>
                            </div>

                            <div>
                                <span class="text-gray-500">Version:</span>
                                <span class="font-mono">{{ $panelVersion }}</span>
                            </div>

                            <div>
                                <span class="text-gray-500">Environment:</span>
                                <span class="font-mono">{{ $env }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg p-5">
                        <h3 class="text-lg font-semibold mb-4">
                            System
                        </h3>

                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-500">PHP:</span>
                                <span class="font-mono">{{ $phpVersion }}</span>
                            </div>

                            <div>
                                <span class="text-gray-500">Laravel:</span>
                                <span class="font-mono">{{ $laravelVersion }}</span>
                            </div>

                            <div>
                                <span class="text-gray-500">Server time:</span>
                                <span class="font-mono">
                                    {{ now()->format('Y-m-d H:i:s') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick links --}}
                <div class="bg-white shadow rounded-lg p-5">
                    <h3 class="text-lg font-semibold mb-3">
                        Quick links
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <a href="{{ route('admin.servers.index') }}"
                           class="underline">
                            Manage servers
                        </a>

                        <a href="{{ route('admin.nodes.index') }}"
                           class="underline">
                            Manage nodes
                        </a>

                        <a href="{{ route('servers.index') }}"
                           class="underline">
                            User view
                        </a>
                    </div>
                </div>

        </div>
    </div>
</div>