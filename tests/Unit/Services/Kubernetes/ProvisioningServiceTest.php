<?php

namespace Tests\Unit\Services\Kubernetes;

use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\User;
use App\Services\Kubernetes\ExecutionSlotManifestBuilder;
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
        $slotBuilder = $this->createMock(ExecutionSlotManifestBuilder::class);
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

        $service = new ProvisioningService($builder, $slotBuilder, $client);

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
        $slotBuilder = $this->createMock(ExecutionSlotManifestBuilder::class);
        $client = $this->createMock(KubernetesClient::class);

        $client->expects($this->once())
            ->method('deleteDeployment')
            ->with("minecraft-{$minecraftServer->id}");

        $client->expects($this->once())
            ->method('deletePvc')
            ->with("minecraft-{$minecraftServer->id}-storage");

        $client->expects($this->once())
            ->method('deleteConfigMap')
            ->with("minecraft-env-{$minecraftServer->id}");

        $service = new ProvisioningService($builder, $slotBuilder, $client);

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
        $slotBuilder = $this->createMock(ExecutionSlotManifestBuilder::class);
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

        $service = new ProvisioningService($builder, $slotBuilder, $client);

        $service->updateMinecraftServer($minecraftServer);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
    }

    public function test_provision_execution_slot_uses_builder_and_client_and_marks_slot_free(): void
    {
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlot::STATUS_PROVISIONING,
        ]);

        $minecraftBuilder = $this->createMock(MinecraftManifestBuilder::class);
        $slotBuilder = $this->createMock(ExecutionSlotManifestBuilder::class);
        $client = $this->createMock(KubernetesClient::class);

        $serviceManifest = ['kind' => 'Service'];

        $slotBuilder->expects($this->once())
            ->method('service')
            ->with($executionSlot)
            ->willReturn($serviceManifest);

        $client->expects($this->once())
            ->method('createService')
            ->with($serviceManifest)
            ->willReturn([]);

        $service = new ProvisioningService($minecraftBuilder, $slotBuilder, $client);

        $service->provisionExecutionSlotService($executionSlot);

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
    }

    public function test_update_execution_slot_updates_existing_service_and_marks_slot_free(): void
    {
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        $minecraftBuilder = $this->createMock(MinecraftManifestBuilder::class);
        $slotBuilder = $this->createMock(ExecutionSlotManifestBuilder::class);
        $client = $this->createMock(KubernetesClient::class);

        $serviceManifest = ['kind' => 'Service'];

        $slotBuilder->expects($this->once())
            ->method('service')
            ->with($executionSlot)
            ->willReturn($serviceManifest);

        $client->expects($this->once())
            ->method('updateService')
            ->with('server-service-1', $serviceManifest)
            ->willReturn([]);

        $service = new ProvisioningService($minecraftBuilder, $slotBuilder, $client);

        $service->updateExecutionSlotService($executionSlot);

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
    }

    public function test_delete_execution_slot_uses_delete_service_call(): void
    {
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlot::STATUS_DELETING,
        ]);

        $minecraftBuilder = $this->createMock(MinecraftManifestBuilder::class);
        $slotBuilder = $this->createMock(ExecutionSlotManifestBuilder::class);
        $client = $this->createMock(KubernetesClient::class);

        $client->expects($this->once())
            ->method('deleteService')
            ->with('server-service-1');

        $service = new ProvisioningService($minecraftBuilder, $slotBuilder, $client);

        $service->deleteExecutionSlotService($executionSlot);
    }
}
