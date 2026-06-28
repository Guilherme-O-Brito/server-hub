<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Jobs\StartMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StartMinecraftServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_start_own_stopped_minecraft_server(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped);
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/start");

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft server is starting']);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Starting, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));

        Queue::assertPushed(StartMinecraftServerJob::class, function (StartMinecraftServerJob $job) use ($minecraftServer, $executionSlot) {
            return $job->serverId === $minecraftServer->id
                && $job->slotId === $executionSlot->id;
        });
    }

    public function test_associated_admin_can_start_minecraft_server(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped);
        $minecraftServer->admins()->attach($admin);

        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $response = $this->actingAs($admin)->post("/servers/minecraft/{$minecraftServer->id}/start");

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft server is starting']);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Starting, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));

        Queue::assertPushed(StartMinecraftServerJob::class, function (StartMinecraftServerJob $job) use ($minecraftServer, $executionSlot) {
            return $job->serverId === $minecraftServer->id
                && $job->slotId === $executionSlot->id;
        });
    }

    public function test_authenticated_user_without_permission_gets_403(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped);
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $response = $this->actingAs($otherUser)->post("/servers/minecraft/{$minecraftServer->id}/start");

        $response->assertStatus(403);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
        Queue::assertNothingPushed();
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_start_rejects_servers_that_are_not_stopped(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, $status);
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/start");

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Minecraft server is not stopped.']);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame($status, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
        Queue::assertNothingPushed();
    }

    public function test_start_rejects_when_no_execution_slot_exists(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/start");

        $response->assertStatus(409);
        $response->assertJson(['message' => 'No execution slot available.']);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        Queue::assertNothingPushed();
    }

    public function test_start_rejects_when_all_execution_slots_are_unavailable(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped);
        $allocatedServer = $this->createMinecraftServer($otherOwner, MinecraftServerStatus::Running);

        $allocatedSlot = ExecutionSlot::factory()->occupied($allocatedServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);
        $deletingSlot = ExecutionSlot::factory()->create([
            'slot_number' => 2,
            'status' => ExecutionSlot::STATUS_DELETING,
        ]);
        $provisioningSlot = ExecutionSlot::factory()->create([
            'slot_number' => 3,
            'status' => ExecutionSlot::STATUS_PROVISIONING,
        ]);
        $failedSlot = ExecutionSlot::factory()->create([
            'slot_number' => 4,
            'status' => ExecutionSlot::STATUS_FAILED,
        ]);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/start");

        $response->assertStatus(409);
        $response->assertJson(['message' => 'No execution slot available.']);

        $minecraftServer->refresh();
        $allocatedSlot->refresh();
        $deletingSlot->refresh();
        $provisioningSlot->refresh();
        $failedSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertTrue($allocatedSlot->server->is($allocatedServer));
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $allocatedSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_DELETING, $deletingSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_PROVISIONING, $provisioningSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_FAILED, $failedSlot->status);
        $this->assertNull($deletingSlot->server_id);
        $this->assertNull($provisioningSlot->server_id);
        $this->assertNull($failedSlot->server_id);
        Queue::assertNothingPushed();
    }

    public function test_second_start_is_rejected_without_allocating_another_slot_or_dispatching_another_job(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped);
        $firstSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);
        $secondSlot = ExecutionSlot::factory()->create([
            'slot_number' => 2,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $firstResponse = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/start");
        $secondResponse = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/start");

        $firstResponse->assertOk();
        $secondResponse->assertStatus(409);
        $secondResponse->assertJson(['message' => 'Minecraft server is not stopped.']);

        $minecraftServer->refresh();
        $firstSlot->refresh();
        $secondSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Starting, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $firstSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $secondSlot->status);
        $this->assertTrue($firstSlot->server->is($minecraftServer));
        $this->assertNull($secondSlot->server_id);
        $this->assertNull($secondSlot->server_type);

        Queue::assertPushed(StartMinecraftServerJob::class, 1);
    }

    public static function invalidServerStatuses(): array
    {
        return [
            'running' => [MinecraftServerStatus::Running],
            'starting' => [MinecraftServerStatus::Starting],
            'failed' => [MinecraftServerStatus::Failed],
            'deleting' => [MinecraftServerStatus::Deleting],
            'provisioning' => [MinecraftServerStatus::Provisioning],
            'stopping' => [MinecraftServerStatus::Stopping],
            'restarting' => [MinecraftServerStatus::Restarting],
            'delete failed' => [MinecraftServerStatus::DeleteFailed],
            'null status' => [null],
        ];
    }

    private function createMinecraftServer(User $owner, ?MinecraftServerStatus $status): MinecraftServer
    {
        return $owner->ownedMinecraftServers()->create([
            'server_name' => 'Start Server',
            'motd' => 'Start motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ]);
    }
}
