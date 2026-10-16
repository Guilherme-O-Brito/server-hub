<?php

namespace Tests\Unit\Events;

use App\Events\MinecraftServerStatusChanged;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class MinecraftServerStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_targets_only_the_server_owner_and_assigned_admins(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create();
        $server->admins()->attach($admin);

        $channels = (new MinecraftServerStatusChanged($server))->broadcastOn();

        $this->assertSame([
            'private-servers.'.$server->id,
            'private-users.'.$owner->id.'.servers',
            'private-users.'.$admin->id.'.servers',
        ], array_map(fn (PrivateChannel $channel) => $channel->name, $channels));
        $this->assertNotContains('private-users.'.$unrelatedUser->id.'.servers', array_map(
            fn (PrivateChannel $channel) => $channel->name,
            $channels
        ));
    }

    public function test_broadcast_payload_contains_the_server_id_and_current_status(): void
    {
        $server = MinecraftServer::factory()->create();
        $event = new MinecraftServerStatusChanged($server);

        $this->assertSame('server.status.changed', $event->broadcastAs());
        $this->assertSame([
            'id' => $server->id,
            'status' => $server->status,
        ], $event->broadcastWith());
    }

    public function test_user_can_subscribe_only_to_their_own_server_updates_channel(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $channelCallback = Broadcast::getChannels()->get('users.{userId}.servers');

        $this->assertTrue($channelCallback($user, $user->id));
        $this->assertFalse($channelCallback($user, $otherUser->id));
    }
}
