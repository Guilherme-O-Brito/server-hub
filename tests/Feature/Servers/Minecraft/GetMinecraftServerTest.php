<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMinecraftServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_get_own_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $version = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'server_name' => 'Owned Server',
            'minecraft_version_id' => $version->id,
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        $response = $this->actingAs($owner)->get("/servers/minecraft/{$minecraftServer->id}");

        $response->assertOk();
        $response->assertJsonPath('id', $minecraftServer->id);
        $response->assertJsonPath('server_name', 'Owned Server');
        $response->assertJsonPath('version.id', $version->id);
        $response->assertJsonPath('version.version', '1.20.1');
        $response->assertJsonPath('execution_slot.id', $executionSlot->id);
        $this->assertIsInt($response->json('id'));
    }

    public function test_associated_admin_can_get_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $version = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $minecraftServer = $this->createMinecraftServer($owner, [
            'server_name' => 'Admin Visible Server',
            'minecraft_version_id' => $version->id,
        ]);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();
        $minecraftServer->admins()->attach($admin->id);

        $response = $this->actingAs($admin)->get("/servers/minecraft/{$minecraftServer->id}");

        $response->assertOk();
        $response->assertJsonPath('id', $minecraftServer->id);
        $response->assertJsonPath('server_name', 'Admin Visible Server');
        $response->assertJsonPath('version.id', $version->id);
        $response->assertJsonPath('execution_slot.id', $executionSlot->id);
    }

    public function test_authenticated_user_without_permission_cannot_get_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->actingAs($otherUser)->get("/servers/minecraft/{$minecraftServer->id}");

        $response->assertForbidden();
    }

    public function test_guest_cannot_get_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->get("/servers/minecraft/{$minecraftServer->id}");

        $response->assertRedirect('/login');
    }

    public function test_get_returns_not_found_for_missing_numeric_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/servers/minecraft/999');

        $response->assertNotFound();
    }

    public function test_get_returns_null_execution_slot_when_server_has_no_slot(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->actingAs($owner)->get("/servers/minecraft/{$minecraftServer->id}");

        $response->assertOk();
        $response->assertJsonPath('id', $minecraftServer->id);
        $response->assertJsonPath('version.id', $minecraftServer->minecraft_version_id);
        $response->assertJsonPath('execution_slot', null);
    }

    public function test_non_numeric_minecraft_server_id_is_not_accepted_by_get_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/servers/minecraft/not-a-number');

        $response->assertNotFound();
    }

    public function test_version_route_is_not_captured_by_minecraft_server_get_route(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $version = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();

        $response = $this->actingAs($user)->get('/servers/minecraft/version');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $version->id,
            'version' => '1.20.1',
            'is_enabled' => true,
        ]);
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
}
