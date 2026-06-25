<?php

namespace Tests\Feature\ExecutionSlot;

use App\Jobs\ExecutionSlot\DeleteExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeleteExecutionSlotTest extends TestCase
{
	use RefreshDatabase;

	public function test_admin_can_delete_only_the_last_execution_slot()
	{
		Queue::fake();

		$admin = User::factory()->create(['is_admin' => true]);

		ExecutionSlot::factory()->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
		]);
		ExecutionSlot::factory()->create([
			'slot_number' => 2,
			'external_port' => 30001,
			'service_name' => 'server-service-2',
		]);
		$lastSlot = ExecutionSlot::factory()->create([
			'slot_number' => 3,
			'external_port' => 30002,
			'service_name' => 'server-service-3',
		]);

		$response = $this->actingAs($admin)->delete('/execution-slot');

		$response->assertOk();
		$response->assertJson(['message' => 'Execution slot successfully deleted']);
		$this->assertDatabaseHas('execution_slots', [
			'slot_number' => 3,
			'external_port' => 30002,
			'status' => ExecutionSlot::STATUS_DELETING,
		]);
		$this->assertDatabaseHas('execution_slots', ['slot_number' => 1]);
		$this->assertDatabaseHas('execution_slots', ['slot_number' => 2]);
		$this->assertDatabaseCount('execution_slots', 3);

		Queue::assertPushed(DeleteExecutionSlotServiceJob::class, function (DeleteExecutionSlotServiceJob $job) use ($lastSlot) {
			return $job->slotId === $lastSlot->id;
		});
	}

	public function test_admin_cannot_delete_execution_slot_when_none_exist()
	{
		$admin = User::factory()->create(['is_admin' => true]);

		$response = $this->actingAs($admin)->delete('/execution-slot');

		$response->assertNotFound();
		$this->assertDatabaseCount('execution_slots', 0);
	}

	public function test_admin_cannot_delete_occupied_last_execution_slot()
	{
		Queue::fake();

		$admin = User::factory()->create(['is_admin' => true]);

		ExecutionSlot::factory()->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
		]);
		ExecutionSlot::factory()->create([
			'slot_number' => 2,
			'external_port' => 30001,
			'service_name' => 'server-service-2',
			'server_id' => 10,
			'server_type' => 'minecraft_server',
			'status' => ExecutionSlot::STATUS_ALLOCATED,
		]);

		$response = $this->actingAs($admin)->delete('/execution-slot');

		$response->assertStatus(409);
		$response->assertJson(['message' => 'Cannot delete occupied slot']);
		$this->assertDatabaseHas('execution_slots', [
			'slot_number' => 2,
			'external_port' => 30001,
			'server_id' => 10,
			'status' => ExecutionSlot::STATUS_ALLOCATED,
		]);
		$this->assertDatabaseCount('execution_slots', 2);
		Queue::assertNothingPushed();
	}

	public function test_non_admin_cannot_delete_execution_slot()
	{
		$user = User::factory()->create(['is_admin' => false]);

		ExecutionSlot::factory()->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
		]);

		$response = $this->actingAs($user)->delete('/execution-slot');

		$response->assertForbidden();
		$this->assertDatabaseCount('execution_slots', 1);
	}

	public function test_guest_cannot_delete_execution_slot()
	{
		ExecutionSlot::factory()->create([
			'slot_number' => 1,
			'external_port' => 30000,
			'service_name' => 'server-service-1',
		]);

		$response = $this->delete('/execution-slot');

		$response->assertRedirect('/login');
		$this->assertDatabaseCount('execution_slots', 1);
	}
}
