<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateMinecraftServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_minecraft_server()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Old Server',
            'level_name' => 'world',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft server successfully modified']);

        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $minecraftServer->id,
            'owner_id' => $owner->id,
            'server_name' => 'Updated Server',
            'level_name' => 'world',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);
    }

    public function test_guest_cannot_update_minecraft_server()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'level_name' => 'world',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $response = $this->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_non_owner_cannot_update_minecraft_server()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'level_name' => 'world',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $response = $this->actingAs($otherUser)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_server_name_is_required_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'level_name' => 'world',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => '',
            'difficulty' => 2,
        ]);

        $response->assertSessionHasErrors('server_name');
    }

    public function test_level_name_remains_unchanged_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'level_name' => 'world',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'difficulty' => 2,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $minecraftServer->id,
            'level_name' => 'world',
        ]);
    }

    public function test_other_server_level_name_remains_unchanged_during_update()
    {
        $owner = User::factory()->create();

        $firstServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'First Server',
            'level_name' => 'world-one',
            'motd' => 'First motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $owner->ownedMinecraftServers()->create([
            'server_name' => 'Second Server',
            'level_name' => 'world-two',
            'motd' => 'Second motd',
            'difficulty' => 2,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$firstServer->id}", [
            'server_name' => 'Updated Server',
            'difficulty' => 2,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $firstServer->id,
            'level_name' => 'world-one',
        ]);

        $this->assertDatabaseHas('minecraft_servers', [
            'server_name' => 'Second Server',
            'level_name' => 'world-two',
        ]);
    }

    public function test_difficulty_is_required_and_in_range_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'level_name' => 'world',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $missingDifficultyResponse = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
        ]);

        $invalidDifficultyResponse = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'difficulty' => 5,
        ]);

        $missingDifficultyResponse->assertSessionHasErrors('difficulty');
        $invalidDifficultyResponse->assertSessionHasErrors('difficulty');
    }

    public function test_force_gamemode_and_allow_flight_must_be_boolean_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'level_name' => 'world',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'difficulty' => 2,
            'force_gamemode' => 'notabool',
            'allow_flight' => 'notabool',
        ]);

        $response->assertSessionHasErrors(['force_gamemode', 'allow_flight']);
    }

    public function test_motd_max_length_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'level_name' => 'world',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $longMotd = str_repeat('a', 300);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'difficulty' => 2,
            'motd' => $longMotd,
        ]);

        $response->assertSessionHasErrors('motd');
    }
}
