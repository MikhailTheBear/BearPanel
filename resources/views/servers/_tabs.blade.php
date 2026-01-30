@php($uuid = $server->uuid)
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex gap-6 text-sm">
        <a href="{{ route('servers.show', $server) }}"
           class="py-2 border-b-2 {{ request()->routeIs('servers.show') ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Overview
        </a>
        <a href="{{ route('servers.console', $server) }}"
           class="py-2 border-b-2 {{ request()->routeIs('servers.console') ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Console
        </a>
        <a href="{{ route('servers.files', $server) }}"
           class="py-2 border-b-2 {{ request()->routeIs('servers.files') ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Files
        </a>
        <a href="{{ route('servers.settings', $server) }}"
           class="py-2 border-b-2 {{ request()->routeIs('servers.settings') ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Settings
        </a>
    </nav>
</div>