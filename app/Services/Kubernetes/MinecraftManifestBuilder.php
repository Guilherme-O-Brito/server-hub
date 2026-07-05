<?php 

namespace App\Services\Kubernetes;

use App\Models\MinecraftServer;

class MinecraftManifestBuilder
{
    
public function pvc(MinecraftServer $minecraftServer): array
    {
        return [
            'apiVersion' => 'v1',
            'kind' => 'PersistentVolumeClaim',
            'metadata' => [
                'name' => $minecraftServer->getStorageName(),
                'namespace' => 'games',
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

    public function server_env(MinecraftServer $minecraftServer): array
    {
        return [
            'apiVersion' => 'v1',
            'kind' => 'ConfigMap',
            'metadata' => [
                'name' => $minecraftServer->getEnvName(),
                'namespace' => 'games'
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
                'PREVENT_PROXY_CONNECTIONS' => 'true',
                'PLAYER_IDLE_TIMEOUT' => '5',
                'ALLOW_FLIGHT' => $minecraftServer->allow_flight ? 'true' : 'false',
                'ANNOUNCE_PLAYER_ACHIEVEMENTS' => 'true',
                'SERVER_NAME' => "$minecraftServer->server_name"
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
                        ],
                    ],

                    'spec' => [
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