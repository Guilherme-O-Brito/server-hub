<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
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

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Old Server',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Stopped,
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

        $updatedServer = MinecraftServer::query()->findOrFail($minecraftServer->id);

        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $updatedServer->id,
            'owner_id' => $owner->id,
            'server_name' => 'Updated Server',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($updatedServer) {
            return $job->serverId === $updatedServer->id;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_owner_cannot_update_minecraft_server_when_it_is_not_stopped(?MinecraftServerStatus $status)
    {
        Queue::fake();

        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Old Server',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ]);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'motd' => 'Updated motd',
            'difficulty' => 2,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Minecraft server is not stopped.']);

        $minecraftServer->refresh();

        $this->assertSame('Old Server', $minecraftServer->server_name);
        $this->assertSame('Old motd', $minecraftServer->motd);
        $this->assertSame(0, $minecraftServer->difficulty);
        $this->assertTrue($minecraftServer->force_gamemode);
        $this->assertFalse($minecraftServer->allow_flight);
        $this->assertSame($status, $minecraftServer->status);
        Queue::assertNothingPushed();
    }

    public function test_guest_cannot_update_minecraft_server()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
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

    public function test_difficulty_is_required_and_in_range_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
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

    public function test_force_gamemode_is_required_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'difficulty' => 2,
            'allow_flight' => true,
        ]);

        $response->assertSessionHasErrors('force_gamemode');
    }

    public function test_allow_flight_is_required_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $response = $this->actingAs($owner)->put("/servers/minecraft/{$minecraftServer->id}", [
            'server_name' => 'Updated Server',
            'difficulty' => 2,
            'force_gamemode' => true,
        ]);

        $response->assertSessionHasErrors('allow_flight');
    }

    public function test_motd_max_length_on_update()
    {
        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Test Server',
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
}
