<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreMinecraftServerAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_admin_to_own_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/admins/{$admin->id}");

        $response->assertCreated();
        $response->assertJson(['message' => 'Admin added successfully.']);

        $this->assertDatabaseHas('minecraft_server_admins', [
            'minecraft_server_id' => $minecraftServer->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_guest_cannot_add_admin_to_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $response = $this->post("/servers/minecraft/{$minecraftServer->id}/admins/{$admin->id}");

        $response->assertRedirect('/login');
    }

    public function test_non_owner_cannot_add_admin_to_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $response = $this->actingAs($otherUser)->post("/servers/minecraft/{$minecraftServer->id}/admins/{$admin->id}");

        $response->assertForbidden();

        $this->assertDatabaseMissing('minecraft_server_admins', [
            'minecraft_server_id' => $minecraftServer->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_owner_cannot_add_themselves_as_admin(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/admins/{$owner->id}");

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Owner is already the owner.']);

        $this->assertDatabaseMissing('minecraft_server_admins', [
            'minecraft_server_id' => $minecraftServer->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_cannot_add_admin_to_nonexistent_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $response = $this->actingAs($owner)->post("/servers/minecraft/999/admins/{$admin->id}");

        $response->assertNotFound();
    }

    public function test_cannot_add_nonexistent_user_as_admin(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/admins/999");

        $response->assertNotFound();
    }
}
