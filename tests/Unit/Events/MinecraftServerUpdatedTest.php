<?php

namespace Tests\Unit\Events;

use App\Events\MinecraftServerUpdated;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class MinecraftServerUpdatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_broadcasts_to_the_server_private_channel_after_commit(): void
    {
        $owner = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create();
        $event = new MinecraftServerUpdated($server);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
        $this->assertSame('server.updated', $event->broadcastAs());
        $this->assertSame(['private-servers.minecraft.'.$server->id], array_map(
            fn (PrivateChannel $channel) => $channel->name,
            $event->broadcastOn()
        ));
    }

    public function test_broadcast_payload_contains_the_server_with_all_update_relations_loaded(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create();
        $server->admins()->attach($admin);
        $server->whitelist()->create(['nickname' => 'WhitelistPlayer']);
        $server->operators()->create(['nickname' => 'OperatorPlayer']);
        ExecutionSlot::factory()->occupied($server)->create();

        $event = new MinecraftServerUpdated($server);
        $payload = $event->broadcastWith();

        $this->assertCount(1, $payload);
        $this->assertSame($server, $payload[0]);
        $this->assertTrue($server->relationLoaded('version'));
        $this->assertTrue($server->relationLoaded('executionSlot'));
        $this->assertTrue($server->relationLoaded('admins'));
        $this->assertTrue($server->relationLoaded('whitelist'));
        $this->assertTrue($server->relationLoaded('operators'));
    }

    public function test_only_users_who_can_view_the_server_may_subscribe_to_its_channel(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $server = MinecraftServer::factory()->for($owner, 'owner')->create();
        $server->admins()->attach($admin);
        $channelCallback = Broadcast::getChannels()->get('servers.minecraft.{minecraftServer}');

        $this->assertTrue($channelCallback($owner, $server));
        $this->assertTrue($channelCallback($admin, $server));
        $this->assertFalse($channelCallback($unrelatedUser, $server));
    }
}
