<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateMinecraftServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_minecraft_server()
    {
        Queue::fake();

        $owner = User::factory()->create();
        $currentVersion = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $newVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();

        $minecraftServer = $this->createMinecraftServer($owner, [
            'server_name' => 'Old Server',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'minecraft_version_id' => $currentVersion->id,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Stopped,
        ]);

        $response = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion)
        );

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft server successfully modified']);

        $updatedServer = MinecraftServer::query()->findOrFail($minecraftServer->id);

        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $updatedServer->id,
            'owner_id' => $owner->id,
            'server_name' => 'Updated Server',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'minecraft_version_id' => $newVersion->id,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);
        $this->assertTrue($updatedServer->version->is($newVersion));

        Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($updatedServer) {
            return $job->serverId === $updatedServer->id;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_owner_cannot_update_minecraft_server_when_it_is_not_stopped(?MinecraftServerStatus $status)
    {
        Queue::fake();

        $owner = User::factory()->create();
        $currentVersion = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $newVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();

        $minecraftServer = $this->createMinecraftServer($owner, [
            'server_name' => 'Old Server',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'minecraft_version_id' => $currentVersion->id,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ]);

        $response = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion)
        );

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Minecraft server is not stopped.']);

        $minecraftServer->refresh();

        $this->assertSame('Old Server', $minecraftServer->server_name);
        $this->assertSame('Old motd', $minecraftServer->motd);
        $this->assertSame(0, $minecraftServer->difficulty);
        $this->assertSame($currentVersion->id, $minecraftServer->minecraft_version_id);
        $this->assertTrue($minecraftServer->force_gamemode);
        $this->assertFalse($minecraftServer->allow_flight);
        $this->assertSame($status, $minecraftServer->status);
        Queue::assertNothingPushed();
    }

    public function test_guest_cannot_update_minecraft_server()
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion)
        );

        $response->assertRedirect('/login');
    }

    public function test_non_owner_cannot_update_minecraft_server()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->actingAs($otherUser)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion)
        );

        $response->assertStatus(403);
    }

    public function test_server_name_is_required_on_update()
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion, ['server_name' => ''])
        );

        $response->assertSessionHasErrors('server_name');
    }

    public function test_difficulty_is_required_and_in_range_on_update()
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $missingDifficultyPayload = $this->validUpdatePayload($newVersion);
        unset($missingDifficultyPayload['difficulty']);

        $missingDifficultyResponse = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $missingDifficultyPayload
        );
        $invalidDifficultyResponse = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion, ['difficulty' => 5])
        );

        $missingDifficultyResponse->assertSessionHasErrors('difficulty');
        $invalidDifficultyResponse->assertSessionHasErrors('difficulty');
    }

    public function test_force_gamemode_and_allow_flight_must_be_boolean_on_update()
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion, [
                'force_gamemode' => 'notabool',
                'allow_flight' => 'notabool',
            ])
        );

        $response->assertSessionHasErrors(['force_gamemode', 'allow_flight']);
    }

    public function test_force_gamemode_is_required_on_update()
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $payload = $this->validUpdatePayload($newVersion);
        unset($payload['force_gamemode']);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", $payload);

        $response->assertSessionHasErrors('force_gamemode');
    }

    public function test_allow_flight_is_required_on_update()
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $payload = $this->validUpdatePayload($newVersion);
        unset($payload['allow_flight']);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", $payload);

        $response->assertSessionHasErrors('allow_flight');
    }

    public function test_motd_max_length_on_update()
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $longMotd = str_repeat('a', 300);

        $response = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion, ['motd' => $longMotd])
        );

        $response->assertSessionHasErrors('motd');
    }

    public function test_minecraft_version_is_required_on_update(): void
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $payload = $this->validUpdatePayload($newVersion);
        unset($payload['minecraft_version_id']);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", $payload);

        $response->assertSessionHasErrors('minecraft_version_id');
    }

    public function test_minecraft_version_must_exist_on_update(): void
    {
        $owner = User::factory()->create();
        $newVersion = MinecraftVersion::factory()->enabled()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($newVersion, ['minecraft_version_id' => 999])
        );

        $response->assertSessionHasErrors('minecraft_version_id');
    }

    public function test_minecraft_version_must_be_enabled_on_update(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $disabledVersion = MinecraftVersion::factory()->disabled()->create();

        $response = $this->actingAs($owner)->put(
            "/servers/minecraft/{$minecraftServer->id}",
            $this->validUpdatePayload($disabledVersion)
        );

        $response->assertSessionHasErrors('minecraft_version_id');
    }

    public static function invalidServerStatuses(): array
    {
        return [
            'running' => [MinecraftServerStatus::Running],
            'starting' => [MinecraftServerStatus::Starting],
            'stopping' => [MinecraftServerStatus::Stopping],
            'failed' => [MinecraftServerStatus::Failed],
            'deleting' => [MinecraftServerStatus::Deleting],
            'provisioning' => [MinecraftServerStatus::Provisioning],
            'restarting' => [MinecraftServerStatus::Restarting],
            'delete failed' => [MinecraftServerStatus::DeleteFailed],
            'null status' => [null],
        ];
    }

    private function createMinecraftServer(User $owner, array $attributes = []): MinecraftServer
    {
        return MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(array_merge([
                'server_name' => 'Test Server',
                'motd' => 'Test motd',
                'difficulty' => 1,
                'force_gamemode' => true,
                'allow_flight' => true,
                'status' => MinecraftServerStatus::Stopped,
            ], $attributes));
    }

    private function validUpdatePayload(MinecraftVersion $minecraftVersion, array $attributes = []): array
    {
        return array_merge([
            'server_name' => 'Updated Server',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'minecraft_version_id' => $minecraftVersion->id,
            'force_gamemode' => false,
            'allow_flight' => true,
        ], $attributes);
    }
}
