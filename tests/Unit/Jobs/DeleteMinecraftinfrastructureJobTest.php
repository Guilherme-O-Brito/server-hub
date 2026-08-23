<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use RuntimeException;
use Tests\TestCase;

class DeleteMinecraftinfrastructureJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_deletes_current_server_and_releases_its_slot_after_successful_cleanup(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('deleteMinecraftServer')
            ->with($this->callback(fn (MinecraftServer $server) => $server->is($minecraftServer)));

        (new DeleteMinecraftinfrastructureJob(
            $minecraftServer->id,
            $minecraftServer->operation_generation,
        ))->handle($service);

        $executionSlot->refresh();

        $this->assertDatabaseMissing('minecraft_servers', ['id' => $minecraftServer->id]);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
    }

    public function test_handle_deletes_current_server_when_it_has_no_slot(): void
    {
        $minecraftServer = $this->createMinecraftServer();

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())->method('deleteMinecraftServer');

        (new DeleteMinecraftinfrastructureJob(
            $minecraftServer->id,
            $minecraftServer->operation_generation,
        ))->handle($service);

        $this->assertDatabaseMissing('minecraft_servers', ['id' => $minecraftServer->id]);
    }

    public function test_handle_ignores_job_with_stale_generation_without_calling_service_or_releasing_slot(): void
    {
        $minecraftServer = $this->createMinecraftServer(['operation_generation' => 9]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('deleteMinecraftServer');

        (new DeleteMinecraftinfrastructureJob($minecraftServer->id, 8))->handle($service);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->status);
        $this->assertSame(9, $minecraftServer->operation_generation);
        $this->assertTrue($executionSlot->isAllocatedTo($minecraftServer));
    }

    public function test_handle_ignores_job_when_server_status_no_longer_matches(): void
    {
        $minecraftServer = $this->createMinecraftServer([
            'status' => MinecraftServerStatus::Provisioning,
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('deleteMinecraftServer');

        (new DeleteMinecraftinfrastructureJob(
            $minecraftServer->id,
            $minecraftServer->operation_generation,
        ))->handle($service);

        $this->assertSame(MinecraftServerStatus::Provisioning, $minecraftServer->refresh()->status);
    }

    public function test_handle_does_not_finalize_when_operation_is_superseded_during_cleanup(): void
    {
        $minecraftServer = $this->createMinecraftServer(['operation_generation' => 4]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('deleteMinecraftServer')
            ->willReturnCallback(function () use ($minecraftServer) {
                MinecraftServer::query()->whereKey($minecraftServer->id)->update([
                    'status' => MinecraftServerStatus::Provisioning,
                    'operation_generation' => 5,
                ]);
            });

        (new DeleteMinecraftinfrastructureJob($minecraftServer->id, 4))->handle($service);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Provisioning, $minecraftServer->status);
        $this->assertSame(5, $minecraftServer->operation_generation);
        $this->assertTrue($executionSlot->isAllocatedTo($minecraftServer));
    }

    public function test_handle_ignores_missing_server(): void
    {
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('deleteMinecraftServer');

        (new DeleteMinecraftinfrastructureJob(999, 1))->handle($service);

        $this->assertDatabaseMissing('minecraft_servers', ['id' => 999]);
    }

    public function test_failed_marks_current_deletion_as_delete_failed_and_records_error(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        (new DeleteMinecraftinfrastructureJob(
            $minecraftServer->id,
            $minecraftServer->operation_generation,
        ))->failed(new RuntimeException('Delete failed'));

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::DeleteFailed, $minecraftServer->status);
        $this->assertSame('Delete failed', $minecraftServer->last_error);
        $this->assertTrue($executionSlot->isAllocatedTo($minecraftServer));
    }

    public function test_failed_ignores_stale_job(): void
    {
        $minecraftServer = $this->createMinecraftServer([
            'last_error' => 'newer error',
            'operation_generation' => 6,
        ]);

        (new DeleteMinecraftinfrastructureJob($minecraftServer->id, 5))
            ->failed(new RuntimeException('stale error'));

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->status);
        $this->assertSame('newer error', $minecraftServer->last_error);
        $this->assertSame(6, $minecraftServer->operation_generation);
    }

    public function test_middleware_uses_shared_server_lock(): void
    {
        $job = new DeleteMinecraftinfrastructureJob(42, 3);

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
            'status' => MinecraftServerStatus::Deleting,
            'last_error' => null,
            'operation_generation' => 4,
        ], $attributes));
    }
}
