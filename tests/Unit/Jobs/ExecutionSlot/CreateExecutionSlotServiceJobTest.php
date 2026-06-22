<?php

namespace Tests\Unit\Jobs\ExecutionSlot;

use App\Jobs\ExecutionSlot\CreateExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateExecutionSlotServiceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_loads_slot_and_calls_provisioning_service(): void
    {
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlot::STATUS_PROVISIONING,
        ]);

        $service = $this->createMock(ProvisioningService::class);
        $service->expects($this->once())
            ->method('provisionExecutionSlotService')
            ->with($this->callback(function (ExecutionSlot $passedSlot) use ($executionSlot) {
                return $passedSlot->is($executionSlot);
            }));

        $job = new CreateExecutionSlotServiceJob($executionSlot->id);

        $job->handle($service);
    }

    public function test_failed_marks_slot_as_failed_and_records_error(): void
    {
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
            'status' => ExecutionSlot::STATUS_PROVISIONING,
        ]);

        $job = new CreateExecutionSlotServiceJob($executionSlot->id);
        $exception = new \RuntimeException('Provision failed');

        $job->failed($exception);

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlot::STATUS_FAILED, $executionSlot->status);
        $this->assertSame('Provision failed', $executionSlot->last_error);
    }
}
