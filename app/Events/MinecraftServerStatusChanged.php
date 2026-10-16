<?php

namespace App\Events;

use App\Models\MinecraftServer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MinecraftServerStatusChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public MinecraftServer $server)
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
            new PrivateChannel("servers.minecraft.{$this->server->id}")
        ];

        $userIds = $this->server->admins()->pluck('users.id');
        $ownerId = $this->server->owner->id;

        $channels[] = new PrivateChannel("users.{$ownerId}.servers");

        foreach ($userIds as $userId) {
            $channels[] = new PrivateChannel("users.{$userId}.servers");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'server.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->server->id,
            'status' => $this->server->status
        ];
    }
}
