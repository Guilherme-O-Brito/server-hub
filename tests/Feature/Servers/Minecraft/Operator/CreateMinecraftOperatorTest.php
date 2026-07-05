<?php

namespace Tests\Feature\Servers\Minecraft\Operator;

use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateMinecraftOperatorTest extends TestCase
{
	use RefreshDatabase;

	public function test_server_owner_can_add_nickname_to_operators(): void
	{
		Queue::fake();

		$owner = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/operators", [
			'nickname' => 'OwnerNick',
		]);

		$response->assertCreated();
		$response->assertJson(['message' => 'User added as operator to this minecraft server successfully']);

		$this->assertDatabaseHas('minecraft_operators', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'OwnerNick',
		]);

		Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
			return $job->serverId === $minecraftServer->id;
		});
	}

	public function test_server_admin_cannot_add_nickname_to_operators(): void
	{
		Queue::fake();

		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftServer->admins()->attach($admin->id);

		$response = $this->actingAs($admin)->post("/servers/minecraft/{$minecraftServer->id}/operators", [
			'nickname' => 'AdminNick',
		]);

		$response->assertForbidden();
		$this->assertDatabaseMissing('minecraft_operators', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'AdminNick',
		]);
	}

	public function test_guest_cannot_add_nickname_to_operators(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$response = $this->post("/servers/minecraft/{$minecraftServer->id}/operators", [
			'nickname' => 'GuestNick',
		]);

		$response->assertRedirect('/login');
		$this->assertDatabaseMissing('minecraft_operators', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'GuestNick',
		]);
	}

	public function test_duplicate_nickname_is_not_allowed_in_the_same_server(): void
	{
		Queue::fake();

		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/operators", [
			'nickname' => 'Steve_01',
		])->assertCreated();

		$response = $this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/operators", [
			'nickname' => 'Steve_01',
		]);

		$response->assertSessionHasErrors('nickname');
		$this->assertDatabaseCount('minecraft_operators', 1);
	}

	public function test_same_nickname_can_be_used_in_different_servers(): void
	{
		Queue::fake();

		$user = User::factory()->create();
		$firstServer = $this->createMinecraftServer($user, ['server_name' => 'First Server']);
		$secondServer = $this->createMinecraftServer($user, ['server_name' => 'Second Server']);

		$firstResponse = $this->actingAs($user)->post("/servers/minecraft/{$firstServer->id}/operators", [
			'nickname' => 'Steve_01',
		]);

		$secondResponse = $this->actingAs($user)->post("/servers/minecraft/{$secondServer->id}/operators", [
			'nickname' => 'Steve_01',
		]);

		$firstResponse->assertCreated();
		$secondResponse->assertCreated();

		$this->assertDatabaseHas('minecraft_operators', [
			'minecraft_server_id' => $firstServer->id,
			'nickname' => 'Steve_01',
		]);

		$this->assertDatabaseHas('minecraft_operators', [
			'minecraft_server_id' => $secondServer->id,
			'nickname' => 'Steve_01',
		]);

		$this->assertDatabaseCount('minecraft_operators', 2);

		Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($firstServer) {
			return $job->serverId === $firstServer->id;
		});

		Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($secondServer) {
			return $job->serverId === $secondServer->id;
		});
	}

	#[DataProvider('invalidServerStatuses')]
	public function test_cannot_create_operator_entry_when_server_is_not_stopped(?MinecraftServerStatus $status): void
	{
		Queue::fake();

		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user, [
			'status' => $status,
		]);

		$response = $this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/operators", [
			'nickname' => 'BlockedNick',
		]);

		$response->assertStatus(409);
		$response->assertJson(['message' => 'Minecraft server is not stopped.']);

		$minecraftServer->refresh();

		$this->assertSame($status, $minecraftServer->status);
		$this->assertDatabaseMissing('minecraft_operators', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'BlockedNick',
		]);
		Queue::assertNothingPushed();
	}

	public function test_deleting_server_cascades_operator_entries(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$minecraftServer->operators()->create([
			'nickname' => 'Steve_01',
		]);

		$minecraftServer->forceDelete();

		$this->assertDatabaseMissing('minecraft_operators', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'Steve_01',
		]);
	}

	public function test_operators_relationship_is_working(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$minecraftServer->operators()->create([
			'nickname' => 'Steve_01',
		]);

		$minecraftServer->refresh();

		$this->assertSame(1, $minecraftServer->operators()->count());
		$this->assertSame('Steve_01', $minecraftServer->operators()->first()->nickname);
		$this->assertDatabaseHas('minecraft_operators', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'Steve_01',
		]);
	}

	public function test_nickname_must_have_maximum_16_characters(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$response = $this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/operators", [
			'nickname' => 'abcdefghijklmnopq',
		]);

		$response->assertSessionHasErrors('nickname');
		$this->assertDatabaseMissing('minecraft_operators', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'abcdefghijklmnopq',
		]);
	}

	public function test_nickname_must_only_contain_letters_numbers_and_underscore(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$response = $this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/operators", [
			'nickname' => 'Steve-01',
		]);

		$response->assertSessionHasErrors('nickname');
		$this->assertDatabaseMissing('minecraft_operators', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'Steve-01',
		]);
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
