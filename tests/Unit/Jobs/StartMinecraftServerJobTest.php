<?php

namespace Tests\Unit\Jobs;

use App\Jobs\StartMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartMinecraftServerJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_loads_server_and_slot_calls_provisioning_service_and_marks_server_running(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'status' => MinecraftServerStatus::Starting,
            'last_error' => 'previous error',
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('updateExecutionSlotService')
            ->with($this->callback(function (ExecutionSlot $passedSlot) use ($executionSlot) {
                return $passedSlot->is($executionSlot);
            }));
        $service->expects($this->once())
            ->method('startMinecraftServer')
            ->with($this->callback(function (MinecraftServer $passedServer) use ($minecraftServer) {
                return $passedServer->is($minecraftServer);
            }));

        $job = new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id);

        $job->handle($service);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Running, $minecraftServer->status);
        $this->assertNull($minecraftServer->last_error);
    }

    public function test_failed_marks_server_stopped_records_error_and_releases_slot(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'status' => MinecraftServerStatus::Starting,
            'last_error' => null,
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        $job = new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id);

        $job->failed(new \RuntimeException('Kubernetes failed'));

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame('Kubernetes failed', $minecraftServer->last_error);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
    }

    public function test_failed_does_not_break_when_execution_slot_does_not_exist(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'status' => MinecraftServerStatus::Starting,
        ]);

        $job = new StartMinecraftServerJob($minecraftServer->id, 999);

        $job->failed(new \RuntimeException('Missing slot failure'));

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame('Missing slot failure', $minecraftServer->last_error);
    }

    public function test_failed_does_not_break_when_minecraft_server_does_not_exist(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'status' => MinecraftServerStatus::Starting,
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);
        $serverId = $minecraftServer->id;
        $minecraftServer->delete();

        $job = new StartMinecraftServerJob($serverId, $executionSlot->id);

        $job->failed(new \RuntimeException('Server disappeared'));

        $executionSlot->refresh();

        $this->assertDatabaseMissing('minecraft_servers', [
            'id' => $serverId,
        ]);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
    }

    private function createMinecraftServer(User $owner, array $attributes = []): MinecraftServer
    {
        return MinecraftServer::factory()->for($owner, 'owner')->create(array_merge([
            'server_name' => 'Job Start Server',
            'motd' => 'Job start motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Stopped,
            'last_error' => null,
        ], $attributes));
    }
}
