<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CreateMinecraftInfrastructureJob;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateMinecraftInfrastructureJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_loads_server_and_calls_provisioning_service(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Job Server',
            'motd' => 'Job motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('provisionMinecraftServer')
            ->with($this->callback(function (MinecraftServer $passedServer) use ($minecraftServer) {
                return $passedServer->is($minecraftServer);
            }));

        $job = new CreateMinecraftInfrastructureJob($minecraftServer->id);

        $job->handle($service);
    }
}
