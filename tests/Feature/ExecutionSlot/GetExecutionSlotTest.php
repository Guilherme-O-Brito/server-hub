<?php

namespace Tests\Feature\ExecutionSlot;

use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetExecutionSlotTest extends TestCase
{
	use RefreshDatabase;

	public function test_authenticated_user_can_get_only_required_execution_slot_attributes_in_slot_number_order()
	{
		$user = User::factory()->create();

		$secondSlot = ExecutionSlot::factory()->create([
			'slot_number' => 2,
			'external_port' => 30001,
			'service_name' => 'server-service-2',
			'hostname' => 'sv2.server-hub.test',
			'last_error' => 'Attribute not used by the frontend',
		]);
		$firstSlot = ExecutionSlot::factory()->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
			'hostname' => 'sv1.server-hub.test',
			'last_error' => 'Attribute not used by the frontend',
		]);

		$response = $this->actingAs($user)->get('/execution-slot');

		$response->assertOk();
		$response->assertExactJson([
			[
				'id' => $firstSlot->id,
				'slot_number' => 1,
				'status' => ExecutionSlot::STATUS_FREE,
				'hostname' => 'sv1.server-hub.test',
				'external_port' => 30000,
				'service_name' => 'server-service-1',
				'server_type' => null,
				'server' => null,
			],
			[
				'id' => $secondSlot->id,
				'slot_number' => 2,
				'status' => ExecutionSlot::STATUS_FREE,
				'hostname' => 'sv2.server-hub.test',
				'external_port' => 30001,
				'service_name' => 'server-service-2',
				'server_type' => null,
				'server' => null,
			],
		]);
	}

	public function test_authenticated_user_can_get_only_required_server_and_owner_attributes_for_occupied_slot()
	{
		$user = User::factory()->create();
		$owner = User::factory()->create(['name' => 'Server owner']);
		$server = MinecraftServer::factory()
			->for($owner, 'owner')
			->create(['server_name' => 'Survival server']);
		$slot = ExecutionSlot::factory()->occupied($server)->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
			'hostname' => 'sv1.server-hub.test',
		]);

		$response = $this->actingAs($user)->get('/execution-slot');

		$response->assertOk();
		$response->assertExactJson([
			[
				'id' => $slot->id,
				'slot_number' => 1,
				'status' => ExecutionSlot::STATUS_ALLOCATED,
				'hostname' => 'sv1.server-hub.test',
				'external_port' => 30000,
				'service_name' => 'server-service-1',
				'server_type' => $server->getMorphClass(),
				'server' => [
					'server_name' => 'Survival server',
					'owner' => [
						'name' => 'Server owner',
					],
				],
			],
		]);
	}

	public function test_authenticated_user_gets_empty_list_when_execution_slots_do_not_exist()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->get('/execution-slot');

		$response->assertOk();
		$response->assertExactJson([]);
	}

	public function test_guest_cannot_get_execution_slots()
	{
		ExecutionSlot::factory()->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
		]);

		$response = $this->get('/execution-slot');

		$response->assertRedirect('/login');
	}
}
