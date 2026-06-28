<?php

namespace App\Services\Kubernetes;

use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;

class ExecutionSlotManifestBuilder
{
    protected function getAppName(ExecutionSlot $executionSlot): string
    {
        $server = $executionSlot->server;

        if ($server) {
            return $server->getDeployName();
        } else {
            return 'no-allocated';
        }

    }
    public function service(ExecutionSlot $executionSlot): array
    {
        return [
            'apiVersion' => 'v1',
            'kind' => 'Service',
            'metadata' => [
                'name' => "{$executionSlot->service_name}",
                'namespace' => 'games',
            ],
            'spec' => [
                'type' => 'NodePort',
                'selector' => [
                    'app' => $this->getAppName($executionSlot),
                ],
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'port' => 25565,
                        'targetPort' => 25565,
                        'nodePort' => $executionSlot->external_port
                    ]
                ]
            ]
        ];
    }
}