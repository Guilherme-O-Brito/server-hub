<?php

namespace Tests\Unit\Actions\ExecutionSlot;

use App\Actions\ExecutionSlot\DeleteExecutionSlotAction;
use App\Jobs\ExecutionSlot\DeleteExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeleteExecutionSlotActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_marks_slot_as_deleting_and_dispatches_job(): void
    {
        Queue::fake();

        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
        ]);

        $action = new DeleteExecutionSlotAction();

        $action->execute($executionSlot);

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlot::STATUS_DELETING, $executionSlot->status);
        $this->assertDatabaseHas('execution_slots', [
            'id' => $executionSlot->id,
            'status' => ExecutionSlot::STATUS_DELETING,
        ]);

        Queue::assertPushed(DeleteExecutionSlotServiceJob::class, function (DeleteExecutionSlotServiceJob $job) use ($executionSlot) {
            return $job->slotId === $executionSlot->id;
        });
    }
}
