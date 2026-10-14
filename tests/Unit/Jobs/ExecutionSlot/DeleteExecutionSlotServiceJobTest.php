<?php

namespace Tests\Unit\Jobs\ExecutionSlot;

use App\Events\ExecutionSlotStatusChanged;
use App\Jobs\ExecutionSlot\DeleteExecutionSlotServiceJob;
use App\enums\ExecutionSlotStatus;
use App\Models\ExecutionSlot;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteExecutionSlotServiceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_deletes_slot_after_successful_cleanup(): void
    {
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlotStatus::Deleting,
            'operation_id' => $this->operationId(1),
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('deleteExecutionSlotService')
            ->with($this->callback(function (ExecutionSlot $passedSlot) use ($executionSlot) {
                return $passedSlot->is($executionSlot);
            }));

        $job = new DeleteExecutionSlotServiceJob(
            $executionSlot->id,
            $executionSlot->operation_id,
        );

        $job->handle($service);

        $this->assertDatabaseMissing('execution_slots', [
            'id' => $executionSlot->id,
        ]);
    }

    public function test_failed_marks_slot_as_failed_and_records_error(): void
    {
        Event::fake();

        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlotStatus::Deleting,
            'operation_id' => $this->operationId(1),
        ]);

        $job = new DeleteExecutionSlotServiceJob(
            $executionSlot->id,
            $executionSlot->operation_id,
        );
        $exception = new \RuntimeException('Delete failed');

        $job->failed($exception);

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlotStatus::Failed, $executionSlot->status);
        $this->assertSame('Delete failed', $executionSlot->last_error);
        Event::assertDispatched(ExecutionSlotStatusChanged::class, function (ExecutionSlotStatusChanged $event) use ($executionSlot) {
            return $event->slot->is($executionSlot)
                && $event->slot->status === ExecutionSlotStatus::Failed;
        });
        Event::assertDispatchedTimes(ExecutionSlotStatusChanged::class, 1);
    }
}
