<?php

namespace Tests\Feature\Servers\Minecraft;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;


class CreateMinecraftServerTest extends TestCase
{
	use RefreshDatabase;

	public function test_authenticated_user_can_create_minecraft_server()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test Server',
			'level_name' => 'world',
			'motd' => 'A cool server',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false
		]);

		$response->assertCreated();
		$response->assertJson(['message' => 'Minecraft server created successfully']);
		$this->assertDatabaseHas('minecraft_servers', [
			'server_name' => 'Test Server',
			'level_name' => 'world',
			'motd' => 'A cool server',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false
		]);
	}

	public function test_guest_cannot_create_minecraft_server()
	{
		$response = $this->post('/servers/minecraft', []);

		$response->assertRedirect('/login');
	}

	public function test_server_name_is_required()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => '',
			'level_name' => 'world',
			'difficulty' => 0
		]);

		$response->assertSessionHasErrors('server_name');
	}

	public function test_level_name_is_required()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
			'level_name' => '',
			'difficulty' => 0
		]);

		$response->assertSessionHasErrors('level_name');
	}

	

	public function test_difficulty_is_required_and_in_range()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
			'level_name' => 'world',
		]);

        $response2 = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
			'level_name' => 'world',
			'difficulty' => 5
		]);

		$response->assertSessionHasErrors('difficulty');
		$response2->assertSessionHasErrors('difficulty');
	}

	public function test_force_gamemode_and_allow_flight_must_be_boolean()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
			'level_name' => 'world',
			'difficulty' => 1,
			'force_gamemode' => 'notabool',
			'allow_flight' => 'notabool'
		]);

		$response->assertSessionHasErrors(['force_gamemode', 'allow_flight']);
	}

	public function test_motd_max_length()
	{
		$user = User::factory()->create();

		$long = str_repeat('a', 300);

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
			'level_name' => 'world',
			'difficulty' => 1,
			'motd' => $long
		]);

		$response->assertSessionHasErrors('motd');
	}
}
