<?php

namespace Tests\Unit\Services\Kubernetes;

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

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Builder Server',
            'motd' => 'Builder motd',
            'difficulty' => 2,
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

        $this->assertSame("minecraft-data-claim-{$minecraftServer->id}", $pvc['metadata']['name']);
        $this->assertSame('games', $pvc['metadata']['namespace']);
        $this->assertSame('local-path', $pvc['spec']['storageClassName']);
        $this->assertSame(['ReadWriteOnce'], $pvc['spec']['accessModes']);
        $this->assertSame('5Gi', $pvc['spec']['resources']['requests']['storage']);

        $this->assertSame("minecraft-env-{$minecraftServer->id}", $configMap['metadata']['name']);
        $this->assertSame('games', $configMap['metadata']['namespace']);
        $this->assertSame('Builder Server', $configMap['data']['SERVER_NAME']);
        $this->assertSame('Builder motd', $configMap['data']['MOTD']);
        $this->assertSame('2', $configMap['data']['DIFFICULTY']);
        $this->assertSame('false', $configMap['data']['FORCE_GAMEMODE']);
        $this->assertSame('true', $configMap['data']['ALLOW_FLIGHT']);
        $this->assertSame('Steve_01,Alex_02', $configMap['data']['WHITELIST']);

        $this->assertSame('Deployment', $deployment['kind']);
        $this->assertSame("minecraft-{$minecraftServer->id}", $deployment['metadata']['name']);
        $this->assertSame('games', $deployment['metadata']['namespace']);
        $this->assertSame(0, $deployment['spec']['replicas']);
        $this->assertSame("minecraft-{$minecraftServer->id}", $deployment['spec']['selector']['matchLabels']['app']);
        $this->assertSame("minecraft-{$minecraftServer->id}", $deployment['spec']['template']['metadata']['labels']['app']);
        $this->assertSame("{$minecraftServer->id}-minecraft-env", $deployment['spec']['template']['spec']['containers'][0]['envFrom'][0]['configMapRef']['name']);
        $this->assertSame("{$minecraftServer->id}-minecraft-data-claim", $deployment['spec']['template']['spec']['volumes'][0]['persistentVolumeClaim']['claimName']);
        $this->assertSame(25565, $deployment['spec']['template']['spec']['containers'][0]['ports'][0]['containerPort']);
        $this->assertSame('itzg/minecraft-server:latest', $deployment['spec']['template']['spec']['containers'][0]['image']);
    }
}