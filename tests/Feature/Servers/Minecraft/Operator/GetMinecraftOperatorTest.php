<?php

namespace Tests\Feature\Servers\Minecraft\Operator;

use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMinecraftOperatorTest extends TestCase
{
	use RefreshDatabase;

	public function test_server_owner_can_get_operator_entries_for_server(): void
	{
		$owner = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$firstOperatorEntry = $minecraftServer->operators()->create([
			'nickname' => 'Steve_01',
		]);

		$secondOperatorEntry = $minecraftServer->operators()->create([
			'nickname' => 'Alex_02',
		]);

		$response = $this->actingAs($owner)->get("/servers/minecraft/{$minecraftServer->id}/operators");

		$response->assertOk();
		$response->assertJsonCount(2);
		$response->assertJsonPath('0.id', $secondOperatorEntry->id);
		$response->assertJsonPath('0.nickname', 'Alex_02');
		$response->assertJsonPath('1.id', $firstOperatorEntry->id);
		$response->assertJsonPath('1.nickname', 'Steve_01');
		$response->assertJsonMissing(['nickname' => 'ForeignNick']);
	}

	public function test_server_admin_can_get_operator_entries_for_server(): void
	{
		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftServer->admins()->attach($admin->id);

		$minecraftServer->operators()->create([
			'nickname' => 'AdminNick',
		]);

		$response = $this->actingAs($admin)->get("/servers/minecraft/{$minecraftServer->id}/operators");

		$response->assertOk();
		$response->assertJsonCount(1);
		$response->assertJsonPath('0.nickname', 'AdminNick');
	}

	public function test_guest_cannot_get_operator_entries_for_server(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$minecraftServer->operators()->create([
			'nickname' => 'Steve_01',
		]);

		$response = $this->get("/servers/minecraft/{$minecraftServer->id}/operators");

		$response->assertRedirect('/login');
	}

	public function test_admin_of_another_server_cannot_get_operator_entries_for_server(): void
	{
		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$otherServer = $this->createMinecraftServer(User::factory()->create());
		$minecraftServer = $this->createMinecraftServer($owner);

		$otherServer->admins()->attach($admin->id);
		$minecraftServer->operators()->create([
			'nickname' => 'OtherServerNick',
		]);

		$response = $this->actingAs($admin)->get("/servers/minecraft/{$minecraftServer->id}/operators");

		$response->assertForbidden();
	}

	private function createMinecraftServer(User $user, array $attributes = []): MinecraftServer
	{
		return MinecraftServer::factory()->for($user, 'owner')->create(array_merge([
			'server_name' => 'Test Server',
			'motd' => 'A cool server',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false,
		], $attributes));
	}
}
