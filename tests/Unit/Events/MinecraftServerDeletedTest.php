<?php

namespace Tests\Unit\Events;

use App\Events\MinecraftServerDeleted;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Tests\TestCase;

class MinecraftServerDeletedTest extends TestCase
{
    public function test_broadcast_targets_the_server_channel_and_each_user_with_access(): void
    {
        $event = new MinecraftServerDeleted(42, [7, 11]);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
        $this->assertSame([
            'private-servers.minecraft.42',
            'private-users.7.servers',
            'private-users.11.servers',
        ], array_map(
            fn (PrivateChannel $channel) => $channel->name,
            $event->broadcastOn()
        ));
    }

    public function test_broadcast_uses_server_deleted_alias_and_sends_the_server_id(): void
    {
        $event = new MinecraftServerDeleted(42, []);

        $this->assertSame('server.deleted', $event->broadcastAs());
        $this->assertSame(['id' => 42], $event->broadcastWith());
    }
}
