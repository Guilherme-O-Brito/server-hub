<?php

namespace Tests\Unit\Jobs;

use App\Jobs\StartMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use RuntimeException;
use Tests\TestCase;

class StartMinecraftServerJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_runs_current_operation_and_marks_server_running(): void
    {
        $minecraftServer = $this->createMinecraftServer([
            'last_error' => 'previous error',
            'operation_id' => $this->operationId(4),
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('updateExecutionSlotService')
            ->with($this->callback(fn (ExecutionSlot $slot) => $slot->is($executionSlot)));
        $service->expects($this->once())
            ->method('startMinecraftServer')
            ->with($this->callback(fn (MinecraftServer $server) => $server->is($minecraftServer)));

        (new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id, $this->operationId(4)))
            ->handle($service);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Running, $minecraftServer->status);
        $this->assertSame($this->operationId(4), $minecraftServer->operation_id);
        $this->assertNull($minecraftServer->last_error);
        $this->assertTrue($executionSlot->isAllocatedTo($minecraftServer));
    }

    public function test_handle_ignores_stale_operation_id_without_calling_services(): void
    {
        $minecraftServer = $this->createMinecraftServer(['operation_id' => $this->operationId(8)]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('updateExecutionSlotService');
        $service->expects($this->never())->method('startMinecraftServer');

        (new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id, $this->operationId(7)))
            ->handle($service);

        $this->assertSame(MinecraftServerStatus::Starting, $minecraftServer->refresh()->status);
        $this->assertTrue($executionSlot->refresh()->isAllocatedTo($minecraftServer));
    }

    public function test_handle_ignores_job_when_server_status_no_longer_matches(): void
    {
        $minecraftServer = $this->createMinecraftServer([
            'status' => MinecraftServerStatus::Deleting,
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('updateExecutionSlotService');
        $service->expects($this->never())->method('startMinecraftServer');

        (new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id, $this->operationId(4)))
            ->handle($service);

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->refresh()->status);
    }

    public function test_handle_ignores_job_when_slot_is_not_allocated_to_server(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $otherServer = $this->createMinecraftServer(['status' => MinecraftServerStatus::Running]);
        $executionSlot = ExecutionSlot::factory()->occupied($otherServer)->create();
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('updateExecutionSlotService');
        $service->expects($this->never())->method('startMinecraftServer');

        (new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id, $this->operationId(4)))
            ->handle($service);

        $this->assertSame(MinecraftServerStatus::Starting, $minecraftServer->refresh()->status);
        $this->assertTrue($executionSlot->refresh()->isAllocatedTo($otherServer));
    }

    public function test_handle_does_not_finalize_when_operation_is_superseded_during_start(): void
    {
        $minecraftServer = $this->createMinecraftServer(['operation_id' => $this->operationId(4)]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())->method('updateExecutionSlotService');
        $service->expects($this->once())
            ->method('startMinecraftServer')
            ->willReturnCallback(function () use ($minecraftServer) {
                MinecraftServer::query()->whereKey($minecraftServer->id)->update([
                    'status' => MinecraftServerStatus::Deleting,
                    'operation_id' => $this->operationId(5),
                ]);
            });

        (new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id, $this->operationId(4)))
            ->handle($service);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->status);
        $this->assertSame($this->operationId(5), $minecraftServer->operation_id);
        $this->assertTrue($executionSlot->isAllocatedTo($minecraftServer));
    }

    public function test_handle_ignores_missing_server(): void
    {
        $executionSlot = ExecutionSlot::factory()->create();
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('updateExecutionSlotService');
        $service->expects($this->never())->method('startMinecraftServer');

        (new StartMinecraftServerJob(999, $executionSlot->id, $this->operationId(1)))->handle($service);

        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->refresh()->status);
    }

    public function test_failed_marks_current_start_as_failed_records_error_and_releases_its_slot(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        (new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id, $this->operationId(4)))
            ->failed(new RuntimeException('Kubernetes failed'));

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Failed, $minecraftServer->status);
        $this->assertSame('Kubernetes failed', $minecraftServer->last_error);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
    }

    public function test_failed_ignores_stale_job_without_releasing_slot(): void
    {
        $minecraftServer = $this->createMinecraftServer([
            'last_error' => 'newer error',
            'operation_id' => $this->operationId(5),
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        (new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id, $this->operationId(4)))
            ->failed(new RuntimeException('stale error'));

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Starting, $minecraftServer->status);
        $this->assertSame('newer error', $minecraftServer->last_error);
        $this->assertTrue($executionSlot->isAllocatedTo($minecraftServer));
    }

    public function test_failed_marks_current_start_failed_when_slot_no_longer_exists(): void
    {
        $minecraftServer = $this->createMinecraftServer();

        (new StartMinecraftServerJob($minecraftServer->id, 999, $this->operationId(4)))
            ->failed(new RuntimeException('Missing slot failure'));

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Failed, $minecraftServer->status);
        $this->assertSame('Missing slot failure', $minecraftServer->last_error);
    }

    public function test_failed_does_not_release_slot_when_server_no_longer_exists(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();
        $serverId = $minecraftServer->id;
        $minecraftServer->delete();

        (new StartMinecraftServerJob($serverId, $executionSlot->id, $this->operationId(4)))
            ->failed(new RuntimeException('Server disappeared'));

        $executionSlot->refresh();

        $this->assertDatabaseMissing('minecraft_servers', ['id' => $serverId]);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertSame($serverId, $executionSlot->server_id);
        $this->assertNull($executionSlot->server);
    }

    public function test_failed_does_not_release_slot_reallocated_to_another_server(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $otherServer = $this->createMinecraftServer(['status' => MinecraftServerStatus::Running]);
        $executionSlot = ExecutionSlot::factory()->occupied($otherServer)->create();

        (new StartMinecraftServerJob($minecraftServer->id, $executionSlot->id, $this->operationId(4)))
            ->failed(new RuntimeException('Start failed'));

        $this->assertSame(MinecraftServerStatus::Failed, $minecraftServer->refresh()->status);
        $this->assertTrue($executionSlot->refresh()->isAllocatedTo($otherServer));
    }

    public function test_middleware_uses_shared_server_lock(): void
    {
        $job = new StartMinecraftServerJob(42, 7, $this->operationId(3));

        $middleware = $job->middleware();

        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);
        $this->assertSame('minecraft-server:42', $middleware[0]->key);
        $this->assertTrue($middleware[0]->shareKey);
    }

    private function createMinecraftServer(array $attributes = []): MinecraftServer
    {
        $owner = User::factory()->create();

        return MinecraftServer::factory()->for($owner, 'owner')->create(array_merge([
            'status' => MinecraftServerStatus::Starting,
            'last_error' => null,
            'operation_id' => $this->operationId(4),
        ], $attributes));
    }
}
