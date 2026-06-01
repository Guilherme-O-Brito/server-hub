<?php

namespace Tests\Feature\Servers\Minecraft\Whitelist;

use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateMinecraftWhitelistTest extends TestCase
{
	use RefreshDatabase;

	public function test_authenticated_user_can_create_whitelist_entry(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$response = $this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'Steve_01',
		]);

		$response->assertCreated();
		$response->assertJson(['message' => 'User added to this minecraft server successfully']);

		$this->assertDatabaseHas('minecraft_whitelists', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'Steve_01',
		]);
	}

	public function test_server_owner_can_add_nickname_to_whitelist(): void
	{
		$owner = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$response = $this->actingAs($owner)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'OwnerNick',
		]);

		$response->assertCreated();
		$this->assertDatabaseHas('minecraft_whitelists', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'OwnerNick',
		]);
	}

	public function test_server_admin_can_add_nickname_to_whitelist(): void
	{
		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$minecraftServer->admins()->attach($admin->id);

		$response = $this->actingAs($admin)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'AdminNick',
		]);

		$response->assertCreated();
		$this->assertDatabaseHas('minecraft_whitelists', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'AdminNick',
		]);
	}

	public function test_user_without_ownership_or_admin_access_cannot_add_nickname_to_whitelist(): void
	{
		$owner = User::factory()->create();
		$unauthorizedUser = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($owner);

		$response = $this->actingAs($unauthorizedUser)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'BlockedNick',
		]);

		$response->assertForbidden();
		$this->assertDatabaseMissing('minecraft_whitelists', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'BlockedNick',
		]);
	}

	public function test_admin_of_another_server_cannot_add_nickname_to_whitelist(): void
	{
		$owner = User::factory()->create();
		$admin = User::factory()->create();
		$otherServer = $this->createMinecraftServer(User::factory()->create());
		$minecraftServer = $this->createMinecraftServer($owner);

		$otherServer->admins()->attach($admin->id);

		$response = $this->actingAs($admin)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'OtherServerAdmin',
		]);

		$response->assertForbidden();
		$this->assertDatabaseMissing('minecraft_whitelists', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'OtherServerAdmin',
		]);
	}

	public function test_duplicate_nickname_is_not_allowed_in_the_same_server(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'Steve_01',
		])->assertCreated();

		$response = $this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'Steve_01',
		]);

		$response->assertSessionHasErrors('nickname');
		$this->assertDatabaseCount('minecraft_whitelists', 1);
	}

	public function test_same_nickname_can_be_used_in_different_servers(): void
	{
		$user = User::factory()->create();
		$firstServer = $this->createMinecraftServer($user, ['server_name' => 'First Server', 'level_name' => 'world-one']);
		$secondServer = $this->createMinecraftServer($user, ['server_name' => 'Second Server', 'level_name' => 'world-two']);

		$firstResponse = $this->actingAs($user)->post("/servers/minecraft/{$firstServer->id}/whitelist", [
			'nickname' => 'Steve_01',
		]);

		$secondResponse = $this->actingAs($user)->post("/servers/minecraft/{$secondServer->id}/whitelist", [
			'nickname' => 'Steve_01',
		]);

		$firstResponse->assertCreated();
		$secondResponse->assertCreated();

		$this->assertDatabaseHas('minecraft_whitelists', [
			'minecraft_server_id' => $firstServer->id,
			'nickname' => 'Steve_01',
		]);

		$this->assertDatabaseHas('minecraft_whitelists', [
			'minecraft_server_id' => $secondServer->id,
			'nickname' => 'Steve_01',
		]);

		$this->assertDatabaseCount('minecraft_whitelists', 2);
	}

	public function test_deleting_server_cascades_whitelist_entries(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'Steve_01',
		])->assertCreated();

		$this->actingAs($user)->delete("/servers/minecraft/{$minecraftServer->id}")
			->assertOk();

		$this->assertDatabaseMissing('minecraft_whitelists', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'Steve_01',
		]);
	}

	public function test_whitelist_relationship_is_working(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$minecraftServer->whitelist()->create([
			'nickname' => 'Steve_01',
		]);

		$minecraftServer->refresh();

		$this->assertSame(1, $minecraftServer->whitelist()->count());
		$this->assertSame('Steve_01', $minecraftServer->whitelist()->first()->nickname);
		$this->assertTrue($minecraftServer->whitelist()->where('nickname', 'Steve_01')->exists());
	}

	public function test_nickname_must_have_maximum_16_characters(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$response = $this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'abcdefghijklmnopq',
		]);

		$response->assertSessionHasErrors('nickname');
		$this->assertDatabaseMissing('minecraft_whitelists', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'abcdefghijklmnopq',
		]);
	}

	public function test_nickname_must_only_contain_letters_numbers_and_underscore(): void
	{
		$user = User::factory()->create();
		$minecraftServer = $this->createMinecraftServer($user);

		$response = $this->actingAs($user)->post("/servers/minecraft/{$minecraftServer->id}/whitelist", [
			'nickname' => 'Steve-01',
		]);

		$response->assertSessionHasErrors('nickname');
		$this->assertDatabaseMissing('minecraft_whitelists', [
			'minecraft_server_id' => $minecraftServer->id,
			'nickname' => 'Steve-01',
		]);
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