<?php 

namespace App\Services\Kubernetes;

use App\MinecraftServerStatus;
use App\Models\MinecraftServer;

class ProvisioningService
{
    public function __construct(protected MinecraftManifestBuilder $builder, protected KubernetesClient $client)
    {}

    public function provisionMinecraftServer(MinecraftServer $server): void
    {
        $this->client->createConfigMap($this->builder->server_env($server));

        $this->client->createPvc($this->builder->pvc($server));

        $this->client->createDeployment($this->builder->deployment($server));

        $server->update([
            'status' => MinecraftServerStatus::Stopped
        ]);
    }

    public function updateMinecraftServer(MinecraftServer $server): void
    {   
        $this->client->updateConfigMap("minecraft-env-{$server->id}", $this->builder->server_env($server));

        $server->update([
            'status' => MinecraftServerStatus::Stopped
        ]);
    }
}