<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexMinecraftServerTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/servers/minecraft';

    public function test_authenticated_user_can_list_owned_and_administered_minecraft_servers(): void
    {
        $user = User::factory()->create();
        $otherOwner = User::factory()->create();

        $ownedVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();
        $adminVersion = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $hiddenVersion = MinecraftVersion::factory()->enabled()->version('1.18.2')->create();

        $ownedServer = $this->createMinecraftServer($user, [
            'server_name' => 'Owned Server',
            'minecraft_version_id' => $ownedVersion->id,
        ]);
        $ownedSlot = ExecutionSlot::factory()->occupied($ownedServer)->create();

        $adminServer = $this->createMinecraftServer($otherOwner, [
            'server_name' => 'Admin Server',
            'minecraft_version_id' => $adminVersion->id,
        ]);
        $adminSlot = ExecutionSlot::factory()->occupied($adminServer)->create();
        $adminServer->admins()->attach($user->id);

        $hiddenServer = $this->createMinecraftServer($otherOwner, [
            'server_name' => 'Hidden Server',
            'minecraft_version_id' => $hiddenVersion->id,
        ]);

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertJsonCount(1);

        $servers = $response->json('0');
        $serverIds = array_column($servers, 'id');

        $this->assertCount(2, $servers);
        $this->assertContains($ownedServer->id, $serverIds);
        $this->assertContains($adminServer->id, $serverIds);
        $this->assertNotContains($hiddenServer->id, $serverIds);

        $ownedServerJson = $this->serverFromResponse($servers, $ownedServer->id);
        $this->assertSame('Owned Server', $ownedServerJson['server_name']);
        $this->assertSame($ownedVersion->id, $ownedServerJson['version']['id']);
        $this->assertSame('1.20.1', $ownedServerJson['version']['version']);
        $this->assertSame($ownedSlot->id, $ownedServerJson['execution_slot']['id']);

        $adminServerJson = $this->serverFromResponse($servers, $adminServer->id);
        $this->assertSame('Admin Server', $adminServerJson['server_name']);
        $this->assertSame($adminVersion->id, $adminServerJson['version']['id']);
        $this->assertSame('1.19.4', $adminServerJson['version']['version']);
        $this->assertSame($adminSlot->id, $adminServerJson['execution_slot']['id']);
    }

    public function test_guest_cannot_list_minecraft_servers(): void
    {
        $response = $this->get(self::ROUTE);

        $response->assertRedirect('/login');
    }

    public function test_index_returns_empty_wrapped_list_when_user_has_no_visible_servers(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $hiddenServer = $this->createMinecraftServer($otherUser);

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertExactJson([[]]);
        $response->assertJsonMissing(['id' => $hiddenServer->id]);
    }

    public function test_index_does_not_duplicate_server_when_user_is_owner_and_admin(): void
    {
        $user = User::factory()->create();
        $server = $this->createMinecraftServer($user);
        $server->admins()->attach($user->id);

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();

        $serverIds = array_column($response->json('0'), 'id');

        $this->assertSame([$server->id], $serverIds);
    }

    public function test_index_returns_null_execution_slot_when_server_has_no_slot(): void
    {
        $user = User::factory()->create();
        $server = $this->createMinecraftServer($user);

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertJsonPath('0.0.id', $server->id);
        $response->assertJsonPath('0.0.execution_slot', null);
        $response->assertJsonPath('0.0.version.id', $server->minecraft_version_id);
    }

    private function createMinecraftServer(User $owner, array $attributes = []): MinecraftServer
    {
        return MinecraftServer::factory()->for($owner, 'owner')->create(array_merge([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ], $attributes));
    }

    private function serverFromResponse(array $servers, int $serverId): array
    {
        $matches = array_values(array_filter(
            $servers,
            fn (array $server): bool => $server['id'] === $serverId
        ));

        $this->assertCount(1, $matches);

        return $matches[0];
    }
}
