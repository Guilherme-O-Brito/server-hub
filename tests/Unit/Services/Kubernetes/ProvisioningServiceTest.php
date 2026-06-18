<?php

namespace Tests\Unit\Services\Kubernetes;

use App\MinecraftServerStatus;
use App\Models\User;
use App\Services\Kubernetes\KubernetesClient;
use App\Services\Kubernetes\MinecraftManifestBuilder;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_provision_minecraft_server_uses_builders_and_client_and_marks_server_stopped(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Provisioned Server',
            'motd' => 'Provisioning motd',
            'difficulty' => 3,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        $minecraftServer->whitelist()->create([
            'nickname' => 'Steve_01',
        ]);

        $builder = $this->createMock(MinecraftManifestBuilder::class);
        $client = $this->createMock(KubernetesClient::class);

        $configMapManifest = ['kind' => 'ConfigMap'];
        $pvcManifest = ['kind' => 'PersistentVolumeClaim'];
        $deploymentManifest = ['kind' => 'Deployment'];

        $builder->expects($this->once())
            ->method('server_env')
            ->with($minecraftServer)
            ->willReturn($configMapManifest);

        $builder->expects($this->once())
            ->method('pvc')
            ->with($minecraftServer)
            ->willReturn($pvcManifest);

        $builder->expects($this->once())
            ->method('deployment')
            ->with($minecraftServer)
            ->willReturn($deploymentManifest);

        $client->expects($this->once())
            ->method('createConfigMap')
            ->with($configMapManifest)
            ->willReturn([]);

        $client->expects($this->once())
            ->method('createPvc')
            ->with($pvcManifest)
            ->willReturn([]);

        $client->expects($this->once())
            ->method('createDeployment')
            ->with($deploymentManifest)
            ->willReturn([]);

        $service = new ProvisioningService($builder, $client);

        $service->provisionMinecraftServer($minecraftServer);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
    }

    public function test_delete_minecraft_server_uses_delete_calls_for_all_resources(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Delete Server',
            'motd' => 'Delete motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $builder = $this->createMock(MinecraftManifestBuilder::class);
        $client = $this->createMock(KubernetesClient::class);

        $client->expects($this->once())
            ->method('deleteDeployment')
            ->with("minecraft-{$minecraftServer->id}");

        $client->expects($this->once())
            ->method('deletePvc')
            ->with("minecraft-data-claim-{$minecraftServer->id}");

        $client->expects($this->once())
            ->method('deleteConfigMap')
            ->with("minecraft-env-{$minecraftServer->id}");

        $service = new ProvisioningService($builder, $client);

        $service->deleteMinecraftServer($minecraftServer);
    }

    public function test_update_minecraft_server_updates_existing_config_map_and_marks_server_stopped(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Updated Server',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        $builder = $this->createMock(MinecraftManifestBuilder::class);
        $client = $this->createMock(KubernetesClient::class);

        $configMapManifest = ['kind' => 'ConfigMap'];

        $builder->expects($this->once())
            ->method('server_env')
            ->with($minecraftServer)
            ->willReturn($configMapManifest);

        $client->expects($this->once())
            ->method('updateConfigMap')
            ->with("minecraft-env-{$minecraftServer->id}", $configMapManifest)
            ->willReturn([]);

        $service = new ProvisioningService($builder, $client);

        $service->updateMinecraftServer($minecraftServer);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
    }
}