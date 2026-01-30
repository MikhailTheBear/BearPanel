<?php

use App\Models\Server;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('servers.{uuid}', function ($user, string $uuid) {
    $server = Server::where('uuid', $uuid)->first();
    return $server ? $server->canAccess($user) : false;
});
