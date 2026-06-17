<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Jobs\CreateMinecraftInfrastructureJob;
use App\Models\MinecraftServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Models\User;


class CreateMinecraftServerTest extends TestCase
{
	use RefreshDatabase;

	public function test_authenticated_user_can_create_minecraft_server()
	{
		Queue::fake();

		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test Server',
			'motd' => 'A cool server',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false
		]);

		$response->assertCreated();
		$response->assertJson(['message' => 'Minecraft server created successfully']);

		$minecraftServer = MinecraftServer::query()
			->where('owner_id', $user->id)
			->where('server_name', 'Test Server')
			->firstOrFail();

		$this->assertDatabaseHas('minecraft_servers', [
			'id' => $minecraftServer->id,
			'owner_id' => $user->id,
			'server_name' => 'Test Server',
			'motd' => 'A cool server',
			'difficulty' => 1,
			'force_gamemode' => true,
			'allow_flight' => false,
		]);

		Queue::assertPushed(CreateMinecraftInfrastructureJob::class, function (CreateMinecraftInfrastructureJob $job) use ($minecraftServer) {
			return $job->serverId === $minecraftServer->id;
		});
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
			'difficulty' => 0
		]);

		$response->assertSessionHasErrors('server_name');
	}

	public function test_difficulty_is_required_and_in_range()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
		]);

        $response2 = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
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
			'difficulty' => 1,
			'force_gamemode' => 'notabool',
			'allow_flight' => 'notabool'
		]);

		$response->assertSessionHasErrors(['force_gamemode', 'allow_flight']);
	}

	public function test_force_gamemode_is_required()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
			'difficulty' => 1,
			'allow_flight' => true,
		]);

		$response->assertSessionHasErrors('force_gamemode');
	}

	public function test_allow_flight_is_required()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
			'difficulty' => 1,
			'force_gamemode' => true,
		]);

		$response->assertSessionHasErrors('allow_flight');
	}

	public function test_motd_max_length()
	{
		$user = User::factory()->create();

		$long = str_repeat('a', 300);

		$response = $this->actingAs($user)->post('/servers/minecraft', [
			'server_name' => 'Test',
			'difficulty' => 1,
			'motd' => $long
		]);

		$response->assertSessionHasErrors('motd');
	}
}
