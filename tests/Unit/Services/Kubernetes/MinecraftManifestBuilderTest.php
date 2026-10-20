<?php

namespace Tests\Unit\Services\Kubernetes;

use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use App\Services\Kubernetes\MinecraftManifestBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinecraftManifestBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builders_extract_expected_data_from_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $minecraftVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Builder Server',
            'motd' => 'Builder motd',
            'difficulty' => 2,
            'minecraft_version_id' => $minecraftVersion->id,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        $minecraftServer->whitelist()->create([
            'nickname' => 'Steve_01',
        ]);

        $minecraftServer->whitelist()->create([
            'nickname' => 'Alex_02',
        ]);

        $builder = new MinecraftManifestBuilder();

        $pvc = $builder->pvc($minecraftServer);
        $configMap = $builder->server_env($minecraftServer);
        $deployment = $builder->deployment($minecraftServer);
        $consoleService = $builder->service($minecraftServer);

        $expectedLabels = [
            'app.kubernetes.io/name' => 'minecraft',
            'app.kubernetes.io/managed-by' => 'server-hub',
            'server-hub.io/server-id' => (string) $minecraftServer->id,
            'server-hub.io/workload' => 'game-server',
            'server-hub.io/game' => 'minecraft',
        ];

        $this->assertSame("minecraft-{$minecraftServer->id}-storage", $pvc['metadata']['name']);
        $this->assertSame('games', $pvc['metadata']['namespace']);
        $this->assertSame($expectedLabels, $pvc['metadata']['labels']);
        $this->assertSame('local-path', $pvc['spec']['storageClassName']);
        $this->assertSame(['ReadWriteOnce'], $pvc['spec']['accessModes']);
        $this->assertSame('5Gi', $pvc['spec']['resources']['requests']['storage']);

        $this->assertSame("minecraft-env-{$minecraftServer->id}", $configMap['metadata']['name']);
        $this->assertSame('games', $configMap['metadata']['namespace']);
        $this->assertSame($expectedLabels, $configMap['metadata']['labels']);
        $this->assertSame('Builder Server', $configMap['data']['SERVER_NAME']);
        $this->assertSame('Builder motd', $configMap['data']['MOTD']);
        $this->assertSame('1.20.1', $configMap['data']['VERSION']);
        $this->assertSame('2', $configMap['data']['DIFFICULTY']);
        $this->assertSame('false', $configMap['data']['FORCE_GAMEMODE']);
        $this->assertSame('true', $configMap['data']['ALLOW_FLIGHT']);
        $this->assertSame('Steve_01,Alex_02', $configMap['data']['WHITELIST']);
        $this->assertSame(28, count($configMap['data']));
        $this->assertSame([
            'ENABLE_RCON' => 'true',
            'RCON_PORT' => '25575',
            'WEBSOCKET_CONSOLE' => 'true',
            'WEBSOCKET_ADDRESS' => '0.0.0.0:25576',
            'WEBSOCKET_LOG_BUFFER_SIZE' => '200',
            'WEBSOCKET_DISABLE_ORIGIN_CHECK' => 'true',
            'WEBSOCKET_DISABLE_AUTHENTICATION' => 'false',
        ], array_intersect_key($configMap['data'], array_flip([
            'ENABLE_RCON',
            'RCON_PORT',
            'WEBSOCKET_CONSOLE',
            'WEBSOCKET_ADDRESS',
            'WEBSOCKET_LOG_BUFFER_SIZE',
            'WEBSOCKET_DISABLE_ORIGIN_CHECK',
            'WEBSOCKET_DISABLE_AUTHENTICATION',
        ])));

        $this->assertSame('Deployment', $deployment['kind']);
        $this->assertSame("minecraft-{$minecraftServer->id}", $deployment['metadata']['name']);
        $this->assertSame('games', $deployment['metadata']['namespace']);
        $this->assertSame($expectedLabels, $deployment['metadata']['labels']);
        $this->assertSame(0, $deployment['spec']['replicas']);
        $this->assertSame("minecraft-{$minecraftServer->id}", $deployment['spec']['selector']['matchLabels']['app']);
        $this->assertSame(
            ['app' => "minecraft-{$minecraftServer->id}"] + $expectedLabels,
            $deployment['spec']['template']['metadata']['labels']
        );
        $this->assertSame("minecraft-env-{$minecraftServer->id}", $deployment['spec']['template']['spec']['containers'][0]['envFrom'][0]['configMapRef']['name']);
        $this->assertSame("minecraft-{$minecraftServer->id}-storage", $deployment['spec']['template']['spec']['volumes'][0]['persistentVolumeClaim']['claimName']);
        $this->assertSame(25565, $deployment['spec']['template']['spec']['containers'][0]['ports'][0]['containerPort']);
        $this->assertSame('itzg/minecraft-server:latest', $deployment['spec']['template']['spec']['containers'][0]['image']);
        $this->assertSame([
            [
                'name' => 'RCON_PASSWORD',
                'valueFrom' => [
                    'secretKeyRef' => [
                        'name' => 'minecraft-secret',
                        'key' => 'password',
                    ],
                ],
            ],
        ], $deployment['spec']['template']['spec']['containers'][0]['env']);

        $this->assertSame([
            'apiVersion' => 'v1',
            'kind' => 'Service',
            'metadata' => [
                'name' => "minecraft-{$minecraftServer->id}-console-service",
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
                    'app' => "minecraft-{$minecraftServer->id}",
                ],
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'port' => 25575,
                        'targetPort' => 25575,
                    ],
                    [
                        'protocol' => 'TCP',
                        'port' => 25576,
                        'targetPort' => 25576,
                    ],
                ],
            ],
        ], $consoleService);
    }
}
