<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeleteMinecraftServerTest extends TestCase
{
	use RefreshDatabase;

	public function test_owner_can_delete_minecraft_server()
	{
		Queue::fake();

		$owner = User::factory()->create();

		$minecraftServer = $owner->ownedMinecraftServers()->create([
			'server_name' => 'Test Server',
			'motd' => 'Test motd',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false,
			'status' => MinecraftServerStatus::Stopped,
		]);

		$response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}");

		$response->assertOk();
		$response->assertJson(['message' => 'Server successfully deleted']);

		$this->assertDatabaseHas('minecraft_servers', [
			'id' => $minecraftServer->id,
			'owner_id' => $owner->id,
			'status' => 'deleting',
		]);

		Queue::assertPushed(DeleteMinecraftinfrastructureJob::class, function (DeleteMinecraftinfrastructureJob $job) use ($minecraftServer) {
			return $job->serverId === $minecraftServer->id;
		});
	}

	#[DataProvider('invalidServerStatuses')]
	public function test_owner_cannot_delete_minecraft_server_when_it_is_not_stopped(?MinecraftServerStatus $status)
	{
		Queue::fake();

		$owner = User::factory()->create();

		$minecraftServer = $owner->ownedMinecraftServers()->create([
			'server_name' => 'Test Server',
			'motd' => 'Test motd',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false,
			'status' => $status,
		]);

		$response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}");

		$response->assertStatus(409);
		$response->assertJson(['message' => 'Minecraft server is not stopped.']);

		$minecraftServer->refresh();

		$this->assertSame($status, $minecraftServer->status);
		$this->assertDatabaseHas('minecraft_servers', [
			'id' => $minecraftServer->id,
			'owner_id' => $owner->id,
			'status' => $status?->value,
		]);
		Queue::assertNothingPushed();
	}

	public function test_guest_cannot_delete_minecraft_server()
	{
		$owner = User::factory()->create();

		$minecraftServer = $owner->ownedMinecraftServers()->create([
			'server_name' => 'Test Server',
			'motd' => 'Test motd',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false,
		]);

		$response = $this->delete("/servers/minecraft/{$minecraftServer->id}");

		$response->assertRedirect('/login');
	}

	public function test_non_owner_cannot_delete_minecraft_server()
	{
		$owner = User::factory()->create();
		$otherUser = User::factory()->create();

		$minecraftServer = $owner->ownedMinecraftServers()->create([
			'server_name' => 'Test Server',
			'motd' => 'Test motd',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false,
		]);

		$response = $this->actingAs($otherUser)->delete("/servers/minecraft/{$minecraftServer->id}");

		$response->assertForbidden();

		$this->assertDatabaseHas('minecraft_servers', [
			'id' => $minecraftServer->id,
			'owner_id' => $owner->id,
		]);
	}

	public function test_cannot_delete_nonexistent_minecraft_server()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->delete('/servers/minecraft/999');

		$response->assertNotFound();
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
