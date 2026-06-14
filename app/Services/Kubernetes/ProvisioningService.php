<?php 

namespace App\Services\Kubernetes;

use App\Models\MinecraftServer;

class ProvisioningService
{
    public function __construct(protected MinecraftManifestBuilder $builder, protected KubernetesClient $client)
    {}

    public function provisionMinecraftServer(MinecraftServer $server): void
    {
        $this->client->createConfigMap($this->builder->server_env($server));

        $this->client->createPvc($this->builder->pvc($server));

        $this->client->createDeployment($this->builder->statefulSet($server));

        $server->update([
            'status' => 'stopped'
        ]);
    }
}