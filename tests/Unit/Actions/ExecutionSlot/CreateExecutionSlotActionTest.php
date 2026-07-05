<?php

namespace Tests\Unit\Actions\ExecutionSlot;

use App\Actions\ExecutionSlot\CreateExecutionSlotAction;
use App\Jobs\ExecutionSlot\CreateExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class CreateExecutionSlotActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_execution_slot_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $action = new CreateExecutionSlotAction();

        $result = $action->execute();

        $this->assertNull($result);

        $executionSlot = ExecutionSlot::query()
            ->where('slot_number', 1)
            ->firstOrFail();

        $this->assertDatabaseHas('execution_slots', [
            'id' => $executionSlot->id,
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlot::STATUS_PROVISIONING,
        ]);

        Queue::assertPushed(CreateExecutionSlotServiceJob::class, function (CreateExecutionSlotServiceJob $job) use ($executionSlot) {
            return $job->slotId === $executionSlot->id;
        });
    }

    public function test_execute_rolls_back_slot_creation_when_transaction_fails(): void
    {
        Queue::fake();

        ExecutionSlot::created(function () {
            throw new RuntimeException('Fail inside create transaction');
        });

        try {
            (new CreateExecutionSlotAction())->execute();
            $this->fail('Expected the create transaction to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fail inside create transaction', $exception->getMessage());
        } finally {
            ExecutionSlot::flushEventListeners();
        }

        $this->assertDatabaseCount('execution_slots', 0);
        Queue::assertNothingPushed();
    }
}
