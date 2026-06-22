<?php 

namespace App\Services\Kubernetes;

use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;

class ProvisioningService
{
    public function __construct(protected MinecraftManifestBuilder $minecraftBuilder, protected ExecutionSlotManifestBuilder $slotBuilder, protected KubernetesClient $client)
    {}

    public function provisionMinecraftServer(MinecraftServer $server): void
    {
        $this->client->createConfigMap($this->minecraftBuilder->server_env($server));

        $this->client->createPvc($this->minecraftBuilder->pvc($server));

        $this->client->createDeployment($this->minecraftBuilder->deployment($server));

        $server->update([
            'status' => MinecraftServerStatus::Stopped
        ]);
    }

    public function updateMinecraftServer(MinecraftServer $server): void
    {   
        $this->client->updateConfigMap("minecraft-env-{$server->id}", $this->minecraftBuilder->server_env($server));

        $server->update([
            'status' => MinecraftServerStatus::Stopped
        ]);
    }

    public function deleteMinecraftServer(MinecraftServer $server): void
    {
        $this->client->deleteDeployment("minecraft-{$server->id}");

        $this->client->deletePvc("minecraft-data-claim-{$server->id}");

        $this->client->deleteConfigMap("minecraft-env-{$server->id}");
    }

    public function provisionExecutionSlotService(ExecutionSlot $slot): void
    {
        $this->client->createService($this->slotBuilder->service($slot));

        $slot->update([
            'status' => ExecutionSlot::STATUS_FREE
        ]);
    }

    public function updateExecutionSlotService(ExecutionSlot $slot):void
    {
        $this->client->updateService($slot->service_name, $this->slotBuilder->service($slot));

        $slot->update([
            'status' => ExecutionSlot::STATUS_FREE,
        ]);
    }

    public function deleteExecutionSlotService(ExecutionSlot $slot): void
    {
        $this->client->deleteService($slot->service_name);
    }
}