<?php

namespace Tests\Feature\Servers\Minecraft\Whitelist;

use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMinecraftWhitelistTest extends TestCase
{
	use RefreshDatabase;

	public function test_authenticated_user_can_get_whitelist_entries_for_server(): void
	{
		$owner = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$firstWhitelistEntry = $minecraftServer->whitelist()->create([
			'nickname' => 'Steve_01',
		]);

		$secondWhitelistEntry = $minecraftServer->whitelist()->create([
			'nickname' => 'Alex_02',
		]);

		$response = $this->actingAs($owner)->get("/servers/minecraft/{$minecraftServer->id}/whitelist");

		$response->assertOk();
		$response->assertJsonCount(2);
		$response->assertJsonPath('0.id', $secondWhitelistEntry->id);
		$response->assertJsonPath('0.nickname', 'Alex_02');
		$response->assertJsonPath('1.id', $firstWhitelistEntry->id);
		$response->assertJsonPath('1.nickname', 'Steve_01');
		$response->assertJsonMissing(['nickname' => 'ForeignNick']);
	}

	public function test_server_admin_can_get_whitelist_entries_for_server(): void
	{
		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftServer->admins()->attach($admin->id);

		$minecraftServer->whitelist()->create([
			'nickname' => 'AdminNick',
		]);

		$response = $this->actingAs($admin)->get("/servers/minecraft/{$minecraftServer->id}/whitelist");

		$response->assertOk();
		$response->assertJsonCount(1);
		$response->assertJsonPath('0.nickname', 'AdminNick');
	}

	public function test_guest_cannot_get_whitelist_entries_for_server(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$minecraftServer->whitelist()->create([
			'nickname' => 'Steve_01',
		]);

		$response = $this->get("/servers/minecraft/{$minecraftServer->id}/whitelist");

		$response->assertRedirect('/login');
	}

	public function test_user_without_ownership_or_admin_access_cannot_get_whitelist_entries_for_server(): void
	{
		$owner = User::factory()->create();
		$unauthorizedUser = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftServer->whitelist()->create([
			'nickname' => 'BlockedNick',
		]);

		$response = $this->actingAs($unauthorizedUser)->get("/servers/minecraft/{$minecraftServer->id}/whitelist");

		$response->assertForbidden();
	}

	public function test_admin_of_another_server_cannot_get_whitelist_entries_for_server(): void
	{
		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$otherServer = $this->createMinecraftServer(User::factory()->create());
		$minecraftServer = $this->createMinecraftServer($owner);

		$otherServer->admins()->attach($admin->id);
		$minecraftServer->whitelist()->create([
			'nickname' => 'OtherServerNick',
		]);

		$response = $this->actingAs($admin)->get("/servers/minecraft/{$minecraftServer->id}/whitelist");

		$response->assertForbidden();
	}

	private function createMinecraftServer(User $user, array $attributes = []): MinecraftServer
	{
		return $user->ownedMinecraftServers()->create(array_merge([
			'server_name' => 'Test Server',
			'level_name' => 'world',
			'motd' => 'A cool server',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false,
		], $attributes));
	}
}