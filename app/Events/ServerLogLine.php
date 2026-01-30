<?php

namespace App\Events;

use App\Models\Server;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ServerLogLine implements ShouldBroadcastNow
{
    public function __construct(
        public string $serverUuid,
        public string $line
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("servers.{$this->serverUuid}")];
    }

    public function broadcastAs(): string
    {
        return 'server.log';
    }
}