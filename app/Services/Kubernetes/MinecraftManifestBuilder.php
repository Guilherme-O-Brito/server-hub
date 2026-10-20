<?php 

namespace App\Services\Kubernetes;

use App\Models\MinecraftServer;

class MinecraftManifestBuilder
{
    
    private function labels(MinecraftServer $minecraftServer): array
    {
        return [
            'app.kubernetes.io/name' => 'minecraft',
            'app.kubernetes.io/managed-by' => 'server-hub',

            'server-hub.io/server-id' => (string) $minecraftServer->id,
            'server-hub.io/workload' => 'game-server',
            'server-hub.io/game' => 'minecraft',
        ];
    }

    public function pvc(MinecraftServer $minecraftServer): array
    {
        return [
            'apiVersion' => 'v1',
            'kind' => 'PersistentVolumeClaim',
            'metadata' => [
                'name' => $minecraftServer->getStorageName(),
                'namespace' => 'games',
                'labels' => $this->labels($minecraftServer),
            ],
            'spec' => [
                'storageClassName' => 'local-path',
                'accessModes' => [
                    'ReadWriteOnce'
                ],
                'resources' => [
                    'requests' => [
                        'storage' => '5Gi'
                    ]
                ]
            ]
        ];
    }

    public function service(MinecraftServer $minecraftServer): array
    {
        return [
            'apiVersion' => 'v1',
            'kind' => 'Service',
            'metadata' => [
                'name' => "{$minecraftServer->getServiceName()}",
                'namespace' => 'games',
                'labels' => [
                    'app.kubernetes.io/name' => 'minecraft-console',
                    'app.kubernetes.io/managed-by' => 'server-hub',
                    'server-hub.io/workload' => 'minecraft-console-service',
                    'server-hub.io/game' => 'minecraft',
                    'server-hub.io/server-id' => (string) $minecraftServer->id,
                ],
            ],
            'spec' => [
                'type' => 'ClusterIP',
                'selector' => [
                    'app' => $minecraftServer->getDeployName(),
                ],
                'ports' => [
                    [   
                        'name' => 'rcon',
                        'protocol' => 'TCP',
                        'port' => 25575,
                        'targetPort' => 25575,
                    ],
                    [   
                        'name' => 'socket',
                        'protocol' => 'TCP',
                        'port' => 25576,
                        'targetPort' => 25576
                    ]
                ]
            ]
        ];
    }

    public function server_env(MinecraftServer $minecraftServer): array
    {
        return [
            'apiVersion' => 'v1',
            'kind' => 'ConfigMap',
            'metadata' => [
                'name' => $minecraftServer->getEnvName(),
                'namespace' => 'games',
                'labels' => $this->labels($minecraftServer),
            ],
            'data' => [
                'EULA' => 'TRUE',
                'MEMORY' => '4096M',
                'VERSION' => "{$minecraftServer->version->version}",
                'MAX_PLAYERS' => '10',
                'MOTD' => "$minecraftServer->motd",
                'USE_AIKAR_FLAGS' => 'true',
                'USE_MEOWICE_FLAGS' => 'true',
                'TZ' => 'America/Sao_Paulo',
                'DIFFICULTY' => "$minecraftServer->difficulty",
                'FORCE_GAMEMODE' => $minecraftServer->force_gamemode ? 'true' : 'false',
                'SIMULATION_DISTANCE' => '12',
                'VIEW_DISTANCE' => '32',
                'ENABLE_WHITELIST' => 'true',
                'WHITELIST' => $minecraftServer->whitelist()->pluck('nickname')->implode(','),
                'OPS' => $minecraftServer->operators()->pluck('nickname')->implode(','),
                'PREVENT_PROXY_CONNECTIONS' => 'false',
                'PLAYER_IDLE_TIMEOUT' => '5',
                'ALLOW_FLIGHT' => $minecraftServer->allow_flight ? 'true' : 'false',
                'ANNOUNCE_PLAYER_ACHIEVEMENTS' => 'true',
                'SERVER_NAME' => "$minecraftServer->server_name",
                'SEED' => "$minecraftServer->seed",
                'ENABLE_RCON' => 'true',
                'RCON_PORT' => '25575',
                'WEBSOCKET_CONSOLE' => 'true',
                'WEBSOCKET_ADDRESS' => '0.0.0.0:25576',
                'WEBSOCKET_LOG_BUFFER_SIZE' => '200',
                //'WEBSOCKET_ALLOWED_ORIGINS' => 'http://server-hub.internal',
                'WEBSOCKET_DISABLE_ORIGIN_CHECK' => 'true',
                'WEBSOCKET_DISABLE_AUTHENTICATION' => 'false',
            ]
        ];
    }

    public function deployment(MinecraftServer $minecraftServer): array
    {
        return [
            'apiVersion' => 'apps/v1',
            'kind' => 'Deployment',

            'metadata' => [
                'name' => $minecraftServer->getDeployName(),
                'namespace' => 'games',
                'labels' => $this->labels($minecraftServer),
            ],

            'spec' => [
                'replicas' => 0,

                'selector' => [
                    'matchLabels' => [
                        'app' => $minecraftServer->getDeployName(),
                    ],
                ],

                'template' => [
                    'metadata' => [
                        'labels' => [
                            'app' => $minecraftServer->getDeployName(),
                        ] + $this->labels($minecraftServer),
                    ],

                    'spec' => [

                        'serviceAccountName' => 'minecraft-runtime',

                        'automountServiceAccountToken' => false,

                        'securityContext' => [
                            'fsGroup' => 1000,
                        ],

                        'containers' => [
                            [
                                'name' => 'minecraft',

                                'image' => 'itzg/minecraft-server:latest',

                                'tty' => true,

                                'stdin' => true,

                                'ports' => [
                                    [
                                        'containerPort' => 25565,
                                    ],
                                ],

                                'envFrom' => [
                                    [
                                        'configMapRef' => [
                                            'name' => $minecraftServer->getEnvName(),
                                        ],
                                    ],
                                ],

                                'env' => [
                                    [
                                        'name' => 'RCON_PASSWORD',
                                        'valueFrom' => [
                                            'secretKeyRef' => [
                                                'name' => "minecraft-secret",
                                                'key' => 'password',
                                            ],
                                        ],
                                    ],
                                ],

                                'volumeMounts' => [
                                    [
                                        'name' => 'minecraft-data',
                                        'mountPath' => '/data',
                                    ],
                                    [
                                        'name' => 'tmp',
                                        'mountPath' => '/tmp',
                                    ],
                                ],

                                'securityContext' => [
                                    'runAsNonRoot' => true,

                                    'runAsUser' => 1000,

                                    'allowPrivilegeEscalation' => false,

                                    'readOnlyRootFilesystem' => true,

                                    'capabilities' => [
                                        'drop' => [
                                            'ALL',
                                        ],
                                    ],

                                    'seccompProfile' => [
                                        'type' => 'RuntimeDefault',
                                    ],
                                ],
                            ],
                        ],

                        'volumes' => [
                            [
                                'name' => 'minecraft-data',

                                'persistentVolumeClaim' => [
                                    'claimName' => $minecraftServer->getStorageName(),
                                ],
                            ],

                            [
                                'name' => 'tmp',

                                'emptyDir' => new \stdClass(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
