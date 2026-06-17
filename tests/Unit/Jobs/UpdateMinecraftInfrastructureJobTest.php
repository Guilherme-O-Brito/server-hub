<?php

namespace Tests\Unit\Jobs;

use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateMinecraftInfrastructureJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_loads_server_and_calls_update_service(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Job Server',
            'motd' => 'Job motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('updateMinecraftServer')
            ->with($this->callback(function (MinecraftServer $passedServer) use ($minecraftServer) {
                return $passedServer->is($minecraftServer);
            }));

        $job = new UpdateMinecraftInfrastructureJob($minecraftServer->id);

        $job->handle($service);
    }
}
