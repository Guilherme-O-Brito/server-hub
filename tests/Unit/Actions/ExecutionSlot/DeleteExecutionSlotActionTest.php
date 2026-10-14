<?php

namespace Tests\Unit\Actions\ExecutionSlot;

use App\Actions\ExecutionSlot\DeleteExecutionSlotAction;
use App\Events\ExecutionSlotStatusChanged;
use App\Exceptions\ExecutionSlotStateException;
use App\Jobs\ExecutionSlot\DeleteExecutionSlotServiceJob;
use App\enums\ExecutionSlotStatus;
use App\Models\ExecutionSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class DeleteExecutionSlotActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_marks_slot_as_deleting_and_dispatches_job(): void
    {
        Queue::fake();
        Event::fake();

        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
        ]);

        $action = new DeleteExecutionSlotAction();

        $action->execute();

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlotStatus::Deleting, $executionSlot->status);
        $this->assertValidOperationId($executionSlot->operation_id);
        $this->assertDatabaseHas('execution_slots', [
            'id' => $executionSlot->id,
            'status' => ExecutionSlotStatus::Deleting,
        ]);

        Queue::assertPushed(DeleteExecutionSlotServiceJob::class, function (DeleteExecutionSlotServiceJob $job) use ($executionSlot) {
            return $job->slotId === $executionSlot->id
                && $job->operationId === $executionSlot->operation_id;
        });
        Event::assertDispatched(ExecutionSlotStatusChanged::class, function (ExecutionSlotStatusChanged $event) use ($executionSlot) {
            return $event->slot->is($executionSlot)
                && $event->slot->status === ExecutionSlotStatus::Deleting;
        });
        Event::assertDispatchedTimes(ExecutionSlotStatusChanged::class, 1);
    }

    #[DataProvider('invalidSlotStatuses')]
    public function test_execute_rejects_invalid_slot_statuses_without_dispatching_job(ExecutionSlotStatus $status): void
    {
        Queue::fake();

        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => $status,
        ]);

        $this->expectException(ExecutionSlotStateException::class);
        $this->expectExceptionMessage('Cannot delete occupied slot');

        try {
            (new DeleteExecutionSlotAction())->execute();
        } finally {
            $executionSlot->refresh();

            $this->assertSame($status, $executionSlot->status);
            Queue::assertNothingPushed();
        }
    }

    public function test_execute_rolls_back_status_change_when_transaction_fails(): void
    {
        Queue::fake();

        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlotStatus::Free,
        ]);

        ExecutionSlot::updated(function () {
            throw new RuntimeException('Fail inside delete transaction');
        });

        try {
            (new DeleteExecutionSlotAction())->execute();
            $this->fail('Expected the delete transaction to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fail inside delete transaction', $exception->getMessage());
        } finally {
            ExecutionSlot::flushEventListeners();
        }

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlotStatus::Free, $executionSlot->status);
        $this->assertDatabaseHas('execution_slots', [
            'id' => $executionSlot->id,
            'status' => ExecutionSlotStatus::Free,
        ]);
        Queue::assertNothingPushed();
    }

    public static function invalidSlotStatuses(): array
    {
        return [
            'provisioning' => [ExecutionSlotStatus::Provisioning],
            'deleting' => [ExecutionSlotStatus::Deleting],
            'allocated' => [ExecutionSlotStatus::Allocated],
            'failed' => [ExecutionSlotStatus::Failed],
        ];
    }
}
