<?php

namespace Tests\Feature\ExecutionSlot;

use App\Jobs\ExecutionSlot\CreateExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateExecutionSlotTest extends TestCase
{
	use RefreshDatabase;

	public function test_admin_can_create_execution_slot_with_initial_values()
	{
		Queue::fake();

		$admin = User::factory()->create(['is_admin' => true]);

		$response = $this->actingAs($admin)->post('/execution-slot', []);

		$response->assertCreated();
		$response->assertJson(['message' => 'Execution slot created successfully']);
		$this->assertDatabaseHas('execution_slots', [
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
			'status' => ExecutionSlot::STATUS_PROVISIONING,
			'server_id' => null,
			'server_type' => null,
		]);

		$executionSlot = ExecutionSlot::query()
			->where('slot_number', 1)
			->firstOrFail();

		Queue::assertPushed(CreateExecutionSlotServiceJob::class, function (CreateExecutionSlotServiceJob $job) use ($executionSlot) {
			return $job->slotId === $executionSlot->id;
		});
	}

	public function test_admin_create_execution_slot_continues_from_last_slot()
	{
		Queue::fake();

		$admin = User::factory()->create(['is_admin' => true]);

		ExecutionSlot::factory()->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
		]);
		ExecutionSlot::factory()->create([
			'slot_number' => 3,
			'external_port' => 30002,
			'service_name' => 'server-service-3',
		]);

		$response = $this->actingAs($admin)->post('/execution-slot', []);

		$response->assertCreated();
		$this->assertDatabaseHas('execution_slots', [
			'slot_number' => 4,
			'external_port' => 30003,
			'service_name' => 'server-service-4',
			'status' => ExecutionSlot::STATUS_PROVISIONING,
		]);
		$this->assertDatabaseCount('execution_slots', 3);

		$executionSlot = ExecutionSlot::query()
			->where('slot_number', 4)
			->firstOrFail();

		Queue::assertPushed(CreateExecutionSlotServiceJob::class, function (CreateExecutionSlotServiceJob $job) use ($executionSlot) {
			return $job->slotId === $executionSlot->id;
		});
	}

	public function test_non_admin_cannot_create_execution_slot()
	{
		$user = User::factory()->create(['is_admin' => false]);

		$response = $this->actingAs($user)->post('/execution-slot', []);

		$response->assertForbidden();
		$this->assertDatabaseCount('execution_slots', 0);
	}

	public function test_guest_cannot_create_execution_slot()
	{
		$response = $this->post('/execution-slot', []);

		$response->assertRedirect('/login');
		$this->assertDatabaseCount('execution_slots', 0);
	}
}
