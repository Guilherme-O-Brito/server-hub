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

class DeleteMinecraftOperatorTest extends TestCase
{
	use RefreshDatabase;

	public function test_server_owner_can_delete_operator_entry(): void
	{
		Queue::fake();

		$owner = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftOperator = $minecraftServer->operators()->create([
			'nickname' => 'OwnerNick',
		]);

		$response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}/operators/{$minecraftOperator->id}");

		$response->assertOk();
		$response->assertJson(['message' => 'Nickname successfully deleted from the operators in this server']);

		$this->assertDatabaseMissing('minecraft_operators', [
			'id' => $minecraftOperator->id,
		]);

		Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
			return $job->serverId === $minecraftServer->id;
		});
	}

	public function test_server_admin_cannot_delete_operator_entry(): void
	{
		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftServer->admins()->attach($admin->id);

		$minecraftOperator = $minecraftServer->operators()->create([
			'nickname' => 'AdminNick',
		]);

		$response = $this->actingAs($admin)->delete("/servers/minecraft/{$minecraftServer->id}/operators/{$minecraftOperator->id}");

		$response->assertForbidden();
		$this->assertDatabaseHas('minecraft_operators', [
			'id' => $minecraftOperator->id,
		]);
	}

	public function test_guest_cannot_delete_operator_entry(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$minecraftOperator = $minecraftServer->operators()->create([
			'nickname' => 'Steve_01',
		]);

		$response = $this->delete("/servers/minecraft/{$minecraftServer->id}/operators/{$minecraftOperator->id}");

		$response->assertRedirect('/login');
		$this->assertDatabaseHas('minecraft_operators', [
			'id' => $minecraftOperator->id,
		]);
	}

	public function test_cannot_delete_operator_entry_from_another_server(): void
	{
		$owner = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);
		$otherServer = $this->createMinecraftServer($owner, ['server_name' => 'Other Server']);

		$minecraftOperator = $otherServer->operators()->create([
			'nickname' => 'ForeignNick',
		]);

		$response = $this->actingAs($owner)->delete("/servers/minecraft/{$minecraftServer->id}/operators/{$minecraftOperator->id}");

		$response->assertNotFound();
		$this->assertDatabaseHas('minecraft_operators', [
			'id' => $minecraftOperator->id,
		]);
	}

	#[DataProvider('invalidServerStatuses')]
	public function test_cannot_delete_operator_entry_when_server_is_not_stopped(?MinecraftServerStatus $status): void
	{
		Queue::fake();

		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user, [
			'status' => $status,
		]);
		$minecraftOperator = $minecraftServer->operators()->create([
			'nickname' => 'BlockedNick',
		]);

		$response = $this->actingAs($user)->delete("/servers/minecraft/{$minecraftServer->id}/operators/{$minecraftOperator->id}");

		$response->assertStatus(409);
		$response->assertJson(['message' => 'Minecraft server is not stopped.']);

		$minecraftServer->refresh();

		$this->assertSame($status, $minecraftServer->status);
		$this->assertDatabaseHas('minecraft_operators', [
			'id' => $minecraftOperator->id,
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
