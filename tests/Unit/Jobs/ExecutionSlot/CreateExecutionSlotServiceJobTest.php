<?php

namespace Tests\Unit\Jobs\ExecutionSlot;

use App\Events\ExecutionSlotStatusChanged;
use App\Jobs\ExecutionSlot\CreateExecutionSlotServiceJob;
use App\enums\ExecutionSlotStatus;
use App\Models\ExecutionSlot;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateExecutionSlotServiceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_loads_slot_and_calls_provisioning_service(): void
    {
        Event::fake();

        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlotStatus::Provisioning,
            'operation_id' => $this->operationId(1),
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('provisionExecutionSlotService')
            ->with($this->callback(function (ExecutionSlot $passedSlot) use ($executionSlot) {
                return $passedSlot->is($executionSlot);
            }));

        $job = new CreateExecutionSlotServiceJob(
            $executionSlot->id,
            $executionSlot->operation_id,
        );

        $job->handle($service);

        $this->assertSame(ExecutionSlotStatus::Free, $executionSlot->refresh()->status);
        Event::assertDispatched(ExecutionSlotStatusChanged::class, function (ExecutionSlotStatusChanged $event) use ($executionSlot) {
            return $event->slot->is($executionSlot)
                && $event->slot->status === ExecutionSlotStatus::Free;
        });
        Event::assertDispatchedTimes(ExecutionSlotStatusChanged::class, 1);
    }

    public function test_failed_marks_slot_as_failed_and_records_error(): void
    {
        Event::fake();

        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlotStatus::Provisioning,
            'operation_id' => $this->operationId(1),
        ]);

        $job = new CreateExecutionSlotServiceJob(
            $executionSlot->id,
            $executionSlot->operation_id,
        );
        $exception = new \RuntimeException('Provision failed');

        $job->failed($exception);

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlotStatus::Failed, $executionSlot->status);
        $this->assertSame('Provision failed', $executionSlot->last_error);
        Event::assertDispatched(ExecutionSlotStatusChanged::class, function (ExecutionSlotStatusChanged $event) use ($executionSlot) {
            return $event->slot->is($executionSlot)
                && $event->slot->status === ExecutionSlotStatus::Failed;
        });
        Event::assertDispatchedTimes(ExecutionSlotStatusChanged::class, 1);
    }
}
