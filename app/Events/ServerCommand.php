<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerCommand implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $serverUuid,
        public string $command,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("servers.{$this->serverUuid}")];
    }

    public function broadcastAs(): string
    {
        return 'server.command';
    }
}
