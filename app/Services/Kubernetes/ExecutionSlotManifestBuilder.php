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
                'labels' => [
                    'app.kubernetes.io/name' => 'execution-slot',
                    'app.kubernetes.io/managed-by' => 'server-hub',
                    'server-hub.io/workload' => 'execution-slot-service',
                    'server-hub.io/slot-number' => (string) $executionSlot->slot_number,
                ],
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
