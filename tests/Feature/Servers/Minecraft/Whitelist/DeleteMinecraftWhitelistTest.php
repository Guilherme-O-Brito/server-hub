<?php

namespace Tests\Feature\Servers\Minecraft\Whitelist;

use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeleteMinecraftWhitelistTest extends TestCase
{
	use RefreshDatabase;

	public function test_authenticated_user_can_delete_whitelist_entry(): void
	{
		Queue::fake();

		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$minecraftWhitelist = $minecraftServer->whitelist()->create([
			'nickname' => 'Steve_01',
		]);

		$response = $this->actingAs($user)->delete("/servers/minecraft/{$minecraftServer->id}/whitelist/{$minecraftWhitelist->id}");

		$response->assertOk();
		$response->assertJson(['message' => 'Nickname successfully deleted from the whitelist']);

		$this->assertDatabaseMissing('minecraft_whitelists', [
			'id' => $minecraftWhitelist->id,
		]);

		Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
			return $job->serverId === $minecraftServer->id;
		});
	}

	public function test_server_owner_can_delete_whitelist_entry(): void
	{
		Queue::fake();

		$owner = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftWhitelist = $minecraftServer->whitelist()->create([
			'nickname' => 'OwnerNick',
		]);

		$response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}/whitelist/{$minecraftWhitelist->id}");

		$response->assertOk();
		$this->assertDatabaseMissing('minecraft_whitelists', [
			'id' => $minecraftWhitelist->id,
		]);

		Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
			return $job->serverId === $minecraftServer->id;
		});
	}

	public function test_server_admin_can_delete_whitelist_entry(): void
	{
		Queue::fake();

		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftServer->admins()->attach($admin->id);

		$minecraftWhitelist = $minecraftServer->whitelist()->create([
			'nickname' => 'AdminNick',
		]);

		$response = $this->actingAs($admin)->delete("/servers/minecraft/{$minecraftServer->id}/whitelist/{$minecraftWhitelist->id}");

		$response->assertOk();
		$this->assertDatabaseMissing('minecraft_whitelists', [
			'id' => $minecraftWhitelist->id,
		]);

		Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
			return $job->serverId === $minecraftServer->id;
		});
	}

	public function test_guest_cannot_delete_whitelist_entry(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$minecraftWhitelist = $minecraftServer->whitelist()->create([
			'nickname' => 'Steve_01',
		]);

		$response = $this->delete("/servers/minecraft/{$minecraftServer->id}/whitelist/{$minecraftWhitelist->id}");

		$response->assertRedirect('/login');
		$this->assertDatabaseHas('minecraft_whitelists', [
			'id' => $minecraftWhitelist->id,
		]);
	}

	public function test_user_without_ownership_or_admin_access_cannot_delete_whitelist_entry(): void
	{
		$owner = User::factory()->create();
		$unauthorizedUser = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftWhitelist = $minecraftServer->whitelist()->create([
			'nickname' => 'BlockedNick',
		]);

		$response = $this->actingAs($unauthorizedUser)->delete("/servers/minecraft/{$minecraftServer->id}/whitelist/{$minecraftWhitelist->id}");

		$response->assertForbidden();
		$this->assertDatabaseHas('minecraft_whitelists', [
			'id' => $minecraftWhitelist->id,
		]);
	}

	public function test_admin_of_another_server_cannot_delete_whitelist_entry(): void
	{
		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$otherServer = $this->createMinecraftServer(User::factory()->create());
		$minecraftServer = $this->createMinecraftServer($owner);

		$otherServer->admins()->attach($admin->id);

		$minecraftWhitelist = $minecraftServer->whitelist()->create([
			'nickname' => 'OtherServerAdmin',
		]);

		$response = $this->actingAs($admin)->delete("/servers/minecraft/{$minecraftServer->id}/whitelist/{$minecraftWhitelist->id}");

		$response->assertForbidden();
		$this->assertDatabaseHas('minecraft_whitelists', [
			'id' => $minecraftWhitelist->id,
		]);
	}

	public function test_cannot_delete_whitelist_entry_from_another_server(): void
	{
		$owner = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);
		$otherServer = $this->createMinecraftServer($owner, ['server_name' => 'Other Server']);

		$minecraftWhitelist = $otherServer->whitelist()->create([
			'nickname' => 'ForeignNick',
		]);

		$response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}/whitelist/{$minecraftWhitelist->id}");

		$response->assertNotFound();
		$this->assertDatabaseHas('minecraft_whitelists', [
			'id' => $minecraftWhitelist->id,
		]);
	}

	#[DataProvider('invalidServerStatuses')]
	public function test_cannot_delete_whitelist_entry_when_server_is_not_stopped(?MinecraftServerStatus $status): void
	{
		Queue::fake();

		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user, [
			'status' => $status,
		]);
		$minecraftWhitelist = $minecraftServer->whitelist()->create([
			'nickname' => 'BlockedNick',
		]);

		$response = $this->actingAs($user)->delete("/servers/minecraft/{$minecraftServer->id}/whitelist/{$minecraftWhitelist->id}");

		$response->assertStatus(409);
		$response->assertJson(['message' => 'Minecraft server is not stopped.']);

		$minecraftServer->refresh();

		$this->assertSame($status, $minecraftServer->status);
		$this->assertDatabaseHas('minecraft_whitelists', [
			'id' => $minecraftWhitelist->id,
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'BlockedNick',
		]);
		Queue::assertNothingPushed();
	}

	private function createMinecraftServer(User $user, array $attributes = []): MinecraftServer
	{
		return MinecraftServer::factory()->for($user, 'owner')->create(array_merge([
			'server_name' => 'Test Server',
			'motd' => 'A cool server',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false,
			'status' => MinecraftServerStatus::Stopped,
		], $attributes));
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
