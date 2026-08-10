<?php

namespace Tests\Feature\ExecutionSlot;

use App\Models\ExecutionSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetExecutionSlotTest extends TestCase
{
	use RefreshDatabase;

	public function test_authenticated_user_can_get_execution_slots()
	{
		$user = User::factory()->create();

		ExecutionSlot::factory()->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
			'hostname' => 'sv1.server-hub.test',
		]);
		ExecutionSlot::factory()->create([
			'slot_number' => 2,
			'external_port' => 30001,
			'service_name' => 'server-service-2',
			'hostname' => 'sv2.server-hub.test',
		]);

		$response = $this->actingAs($user)->get('/execution-slot');

		$response->assertOk();
		$response->assertJsonCount(2);
		$response->assertJsonFragment([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
			'hostname' => 'sv1.server-hub.test',
			'status' => ExecutionSlot::STATUS_FREE,
		]);
		$response->assertJsonFragment([
			'slot_number' => 2,
			'external_port' => 30001,
			'service_name' => 'server-service-2',
			'hostname' => 'sv2.server-hub.test',
			'status' => ExecutionSlot::STATUS_FREE,
		]);
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
