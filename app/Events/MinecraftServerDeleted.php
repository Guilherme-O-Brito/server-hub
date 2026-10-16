<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MinecraftServerDeleted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public readonly int $serverId, public readonly array $userIds,)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("servers.minecraft.{$this->serverId}"),
        ];

        foreach ($this->userIds as $userId) {
            $channels[] = new PrivateChannel("users.{$userId}.servers");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'server.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->serverId,
        ];
    }
}
