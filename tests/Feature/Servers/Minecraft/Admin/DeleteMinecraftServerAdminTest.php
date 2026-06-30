<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteMinecraftServerAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_remove_admin_from_own_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $minecraftServer->admins()->attach($admin->id);

        $response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}/admins/{$admin->id}");

        $response->assertOk();
        $response->assertJson(['message' => 'Admin removed successfully.']);

        $this->assertDatabaseMissing('minecraft_server_admins', [
            'minecraft_server_id' => $minecraftServer->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_guest_cannot_remove_admin_from_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $minecraftServer->admins()->attach($admin->id);

        $response = $this->delete("/servers/minecraft/{$minecraftServer->id}/admins/{$admin->id}");

        $response->assertRedirect('/login');
    }

    public function test_non_owner_cannot_remove_admin_from_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $minecraftServer->admins()->attach($admin->id);

        $response = $this->actingAs($otherUser)->delete("/servers/minecraft/{$minecraftServer->id}/admins/{$admin->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('minecraft_server_admins', [
            'minecraft_server_id' => $minecraftServer->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_owner_cannot_remove_user_who_is_not_an_admin(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}/admins/{$user->id}");

        $response->assertNotFound();
    }

    public function test_cannot_remove_admin_from_nonexistent_minecraft_server(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($owner)->delete("/servers/minecraft/999/admins/{$user->id}");

        $response->assertNotFound();
    }

    public function test_cannot_remove_nonexistent_user_from_minecraft_server(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}/admins/999");

        $response->assertNotFound();
    }
}
