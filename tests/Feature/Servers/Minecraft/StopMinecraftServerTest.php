<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Jobs\StopMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StopMinecraftServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_stop_own_running_minecraft_server(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        [$minecraftServer, $executionSlot] = $this->createRunningServerWithSlot($owner);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/stop");

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft server is stopping']);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Stopping, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));

        Queue::assertPushed(StopMinecraftServerJob::class, function (StopMinecraftServerJob $job) use ($minecraftServer, $executionSlot) {
            return $job->serverId === $minecraftServer->id
                && $job->slotId === $executionSlot->id;
        });
    }

    public function test_associated_admin_can_stop_minecraft_server(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $admin = User::factory()->create();
        [$minecraftServer, $executionSlot] = $this->createRunningServerWithSlot($owner);
        $minecraftServer->admins()->attach($admin);

        $response = $this->actingAs($admin)->post("/servers/minecraft/{$minecraftServer->id}/stop");

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft server is stopping']);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Stopping, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));

        Queue::assertPushed(StopMinecraftServerJob::class, function (StopMinecraftServerJob $job) use ($minecraftServer, $executionSlot) {
            return $job->serverId === $minecraftServer->id
                && $job->slotId === $executionSlot->id;
        });
    }

    public function test_authenticated_user_without_permission_gets_403(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$minecraftServer, $executionSlot] = $this->createRunningServerWithSlot($owner);

        $response = $this->actingAs($otherUser)->post("/servers/minecraft/{$minecraftServer->id}/stop");

        $response->assertStatus(403);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Running, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));
        Queue::assertNothingPushed();
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_stop_rejects_servers_that_are_not_running(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, $status);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/stop");

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Minecraft server is not running.']);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame($status, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));
        Queue::assertNothingPushed();
    }

    public function test_stop_rejects_running_server_without_execution_slot(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Running);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/stop");

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Minecraft server has no execution slot.']);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Running, $minecraftServer->status);
        Queue::assertNothingPushed();
    }

    public function test_second_stop_is_rejected_without_dispatching_another_job_or_releasing_slot(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        [$minecraftServer, $executionSlot] = $this->createRunningServerWithSlot($owner);

        $firstResponse = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/stop");
        $secondResponse = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/stop");

        $firstResponse->assertOk();
        $secondResponse->assertStatus(409);
        $secondResponse->assertJson(['message' => 'Minecraft server is not running.']);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Stopping, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));

        Queue::assertPushed(StopMinecraftServerJob::class, 1);
    }

    public static function invalidServerStatuses(): array
    {
        return [
            'stopped' => [MinecraftServerStatus::Stopped],
            'starting' => [MinecraftServerStatus::Starting],
            'stopping' => [MinecraftServerStatus::Stopping],
            'failed' => [MinecraftServerStatus::Failed],
            'deleting' => [MinecraftServerStatus::Deleting],
            'provisioning' => [MinecraftServerStatus::Provisioning],
            'restarting' => [MinecraftServerStatus::Restarting],
            'delete failed' => [MinecraftServerStatus::DeleteFailed],
            'null status' => [null],
        ];
    }

    private function createRunningServerWithSlot(User $owner): array
    {
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Running);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        return [$minecraftServer, $executionSlot];
    }

    private function createMinecraftServer(User $owner, ?MinecraftServerStatus $status): MinecraftServer
    {
        return MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Stop Server',
            'motd' => 'Stop motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ]);
    }
}
