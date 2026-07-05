<?php

namespace Tests\Unit\Jobs;

use App\Jobs\StopMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StopMinecraftServerJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_loads_server_calls_provisioning_service_marks_stopped_and_releases_slot(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'status' => MinecraftServerStatus::Stopping,
            'last_error' => 'previous error',
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('stopMinecraftServer')
            ->with($this->callback(function (MinecraftServer $passedServer) use ($minecraftServer, $executionSlot) {
                $executionSlot->refresh();

                return $passedServer->is($minecraftServer)
                    && $executionSlot->status === ExecutionSlot::STATUS_ALLOCATED
                    && $executionSlot->server_id === $minecraftServer->id
                    && $executionSlot->server_type === $minecraftServer->getMorphClass();
            }));

        $job = new StopMinecraftServerJob($minecraftServer->id, $executionSlot->id);

        $job->handle($service);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertNull($minecraftServer->last_error);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
    }

    public function test_handle_keeps_server_and_slot_unchanged_if_provisioning_service_fails(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'status' => MinecraftServerStatus::Stopping,
            'last_error' => null,
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('stopMinecraftServer')
            ->willThrowException(new \RuntimeException('Kubernetes stop failed'));

        $job = new StopMinecraftServerJob($minecraftServer->id, $executionSlot->id);

        try {
            $job->handle($service);
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Kubernetes stop failed', $exception->getMessage());
        }

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Stopping, $minecraftServer->status);
        $this->assertNull($minecraftServer->last_error);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));
    }

    public function test_failed_marks_server_running_records_error_and_does_not_release_slot(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'status' => MinecraftServerStatus::Stopping,
            'last_error' => null,
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        $job = new StopMinecraftServerJob($minecraftServer->id, $executionSlot->id);

        $job->failed(new \RuntimeException('Stop failed'));

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Running, $minecraftServer->status);
        $this->assertSame('Stop failed', $minecraftServer->last_error);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));
    }

    public function test_failed_does_not_break_when_minecraft_server_does_not_exist(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'status' => MinecraftServerStatus::Stopping,
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);
        $serverId = $minecraftServer->id;
        $minecraftServer->delete();

        $job = new StopMinecraftServerJob($serverId, $executionSlot->id);

        $job->failed(new \RuntimeException('Server disappeared'));

        $executionSlot->refresh();

        $this->assertDatabaseMissing('minecraft_servers', [
            'id' => $serverId,
        ]);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertNull($executionSlot->server);
    }

    private function createMinecraftServer(User $owner, array $attributes = []): MinecraftServer
    {
        return MinecraftServer::factory()->for($owner, 'owner')->create(array_merge([
            'server_name' => 'Job Stop Server',
            'motd' => 'Job stop motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Running,
            'last_error' => null,
        ], $attributes));
    }
}
