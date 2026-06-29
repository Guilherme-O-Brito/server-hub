<?php

namespace Tests\Feature\Servers\Minecraft\Admin;

use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMinecraftServerAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_get_admins_for_own_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $zAdmin = User::factory()->create([
            'name' => 'Zed Admin',
            'email' => 'zed@example.com',
        ]);
        $aAdmin = User::factory()->create([
            'name' => 'Alice Admin',
            'email' => 'alice@example.com',
        ]);
        $unrelatedUser = User::factory()->create([
            'name' => 'Other User',
        ]);

        $minecraftServer->admins()->attach([$zAdmin->id, $aAdmin->id]);

        $response = $this->actingAs($owner)->get("/servers/minecraft/{$minecraftServer->id}/admins");

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonPath('0.id', $aAdmin->id);
        $response->assertJsonPath('0.name', 'Alice Admin');
        $response->assertJsonPath('0.email', 'alice@example.com');
        $response->assertJsonPath('1.id', $zAdmin->id);
        $response->assertJsonPath('1.name', 'Zed Admin');
        $response->assertJsonPath('1.email', 'zed@example.com');
        $response->assertJsonMissing(['id' => $owner->id]);
        $response->assertJsonMissing(['id' => $unrelatedUser->id]);
    }

    public function test_owner_gets_empty_list_when_server_has_no_admins(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->actingAs($owner)->get("/servers/minecraft/{$minecraftServer->id}/admins");

        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function test_guest_cannot_get_minecraft_server_admins(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $response = $this->get("/servers/minecraft/{$minecraftServer->id}/admins");

        $response->assertRedirect('/login');
    }

    public function test_non_owner_cannot_get_minecraft_server_admins(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $admin = User::factory()->create();
        $minecraftServer->admins()->attach($admin->id);

        $response = $this->actingAs($otherUser)->get("/servers/minecraft/{$minecraftServer->id}/admins");

        $response->assertForbidden();
    }

    public function test_associated_admin_cannot_get_minecraft_server_admins(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $admin = User::factory()->create();
        $minecraftServer->admins()->attach($admin->id);

        $response = $this->actingAs($admin)->get("/servers/minecraft/{$minecraftServer->id}/admins");

        $response->assertForbidden();
    }

    public function test_cannot_get_admins_from_nonexistent_minecraft_server(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->get('/servers/minecraft/999/admins');

        $response->assertNotFound();
    }

    private function createMinecraftServer(User $owner, array $attributes = []): MinecraftServer
    {
        return $owner->ownedMinecraftServers()->create(array_merge([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ], $attributes));
    }
}
