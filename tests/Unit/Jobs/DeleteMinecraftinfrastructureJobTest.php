<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteMinecraftinfrastructureJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_deletes_server_after_successful_cleanup(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Delete Server',
            'motd' => 'Delete motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('deleteMinecraftServer')
            ->with($this->callback(function (MinecraftServer $passedServer) use ($minecraftServer) {
                return $passedServer->is($minecraftServer);
            }));

        $job = new DeleteMinecraftinfrastructureJob($minecraftServer->id);

        $job->handle($service);

        $this->assertDatabaseMissing('minecraft_servers', [
            'id' => $minecraftServer->id,
        ]);
    }

    public function test_failed_marks_server_as_failed_and_records_error(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Delete Server',
            'motd' => 'Delete motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $job = new DeleteMinecraftinfrastructureJob($minecraftServer->id);
        $exception = new \RuntimeException('Delete failed');

        $job->failed($exception);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::DeleteFailed, $minecraftServer->status);
        $this->assertSame('Delete failed', $minecraftServer->last_error);
    }
}
