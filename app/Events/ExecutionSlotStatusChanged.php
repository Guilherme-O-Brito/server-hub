<?php

namespace App\Events;

use App\Models\ExecutionSlot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExecutionSlotStatusChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ExecutionSlot $slot)
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
            new PrivateChannel('execution-slots'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'slot.status.changed';
    }

    public function broadcastWith(): array
    {
        $server = $this->slot->server;

        return [
            'id' => $this->slot->id,
            'status' => $this->slot->status,
            'server' => $server ? [
                'server_name' => $server->server_name,
                'owner' => [
                    'name' => $server->owner->name
                ]
            ]: null
        ];
    }
}
