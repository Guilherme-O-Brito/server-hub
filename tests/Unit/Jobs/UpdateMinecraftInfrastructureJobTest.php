<?php

namespace Tests\Unit\Jobs;

use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use RuntimeException;
use Tests\TestCase;

class UpdateMinecraftInfrastructureJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_runs_current_update_and_marks_server_stopped(): void
    {
        $minecraftServer = $this->createMinecraftServer([
            'last_error' => 'previous error',
            'operation_generation' => 4,
        ]);
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('updateMinecraftServer')
            ->with($this->callback(fn (MinecraftServer $server) => $server->is($minecraftServer)));

        (new UpdateMinecraftInfrastructureJob($minecraftServer->id, 4))->handle($service);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame(4, $minecraftServer->operation_generation);
        $this->assertNull($minecraftServer->last_error);
    }

    public function test_handle_ignores_stale_generation_without_calling_service(): void
    {
        $minecraftServer = $this->createMinecraftServer(['operation_generation' => 8]);
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('updateMinecraftServer');

        (new UpdateMinecraftInfrastructureJob($minecraftServer->id, 7))->handle($service);

        $this->assertSame(MinecraftServerStatus::Provisioning, $minecraftServer->refresh()->status);
        $this->assertSame(8, $minecraftServer->operation_generation);
    }

    public function test_handle_ignores_job_when_server_status_no_longer_matches(): void
    {
        $minecraftServer = $this->createMinecraftServer([
            'status' => MinecraftServerStatus::Deleting,
        ]);
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('updateMinecraftServer');

        (new UpdateMinecraftInfrastructureJob($minecraftServer->id, 4))->handle($service);

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->refresh()->status);
    }

    public function test_handle_does_not_finalize_when_operation_is_superseded_during_update(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('updateMinecraftServer')
            ->willReturnCallback(function () use ($minecraftServer) {
                MinecraftServer::query()->whereKey($minecraftServer->id)->update([
                    'status' => MinecraftServerStatus::Deleting,
                    'operation_generation' => 5,
                ]);
            });

        (new UpdateMinecraftInfrastructureJob($minecraftServer->id, 4))->handle($service);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->status);
        $this->assertSame(5, $minecraftServer->operation_generation);
    }

    public function test_handle_ignores_missing_server(): void
    {
        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->never())->method('updateMinecraftServer');

        (new UpdateMinecraftInfrastructureJob(999, 1))->handle($service);

        $this->assertDatabaseMissing('minecraft_servers', ['id' => 999]);
    }

    public function test_failed_marks_current_update_failed_and_records_error(): void
    {
        $minecraftServer = $this->createMinecraftServer();

        (new UpdateMinecraftInfrastructureJob($minecraftServer->id, 4))
            ->failed(new RuntimeException('Update failed'));

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Failed, $minecraftServer->status);
        $this->assertSame('Update failed', $minecraftServer->last_error);
    }

    public function test_failed_ignores_stale_job(): void
    {
        $minecraftServer = $this->createMinecraftServer([
            'last_error' => 'newer error',
            'operation_generation' => 5,
        ]);

        (new UpdateMinecraftInfrastructureJob($minecraftServer->id, 4))
            ->failed(new RuntimeException('stale error'));

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Provisioning, $minecraftServer->status);
        $this->assertSame('newer error', $minecraftServer->last_error);
        $this->assertSame(5, $minecraftServer->operation_generation);
    }

    public function test_middleware_uses_shared_server_lock(): void
    {
        $job = new UpdateMinecraftInfrastructureJob(42, 3);

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
            'status' => MinecraftServerStatus::Provisioning,
            'last_error' => null,
            'operation_generation' => 4,
        ], $attributes));
    }
}
