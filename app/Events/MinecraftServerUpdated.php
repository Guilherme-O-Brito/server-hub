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

class MinecraftServerUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
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
        return [
            new PrivateChannel("servers.minecraft.{$this->server->id}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'server.updated';
    }

    public function broadcastWith(): array
    {

        $this->server->load([
            'version',
            'executionSlot',
            'admins',
            'whitelist',
            'operators'
        ]);

        return [
            $this->server
        ];
    }
}
