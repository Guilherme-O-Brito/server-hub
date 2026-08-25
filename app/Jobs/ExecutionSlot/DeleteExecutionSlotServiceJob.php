<?php

namespace App\Jobs\ExecutionSlot;

use App\Models\ExecutionSlot;
use App\Services\Kubernetes\ProvisioningService;
use DB;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class DeleteExecutionSlotServiceJob implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $slotId, public string $operationId)
    {
        //
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("execution-slot:{$this->slotId}"))->shared(),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(ProvisioningService $provisioningService): void
    {
        $slot = ExecutionSlot::findOrFail($this->slotId);

        if (! $slot || $slot->status !== ExecutionSlot::STATUS_DELETING || $slot->operation_id !== $this->operationId) {
            return;
        }

        $provisioningService->deleteExecutionSlotService($slot);

        DB::transaction(function () {
            $slot = ExecutionSlot::query()->lockForUpdate()->find($this->slotId);

            if (! $slot || $slot->status !== ExecutionSlot::STATUS_DELETING || $slot->operation_id !== $this->operationId) {
                return;
            }

            $slot->delete();
        });
    }

    public function failed(\Throwable $exception): void
    {
        DB::transaction(function () use ($exception) {
            $slot = ExecutionSlot::query()->lockForUpdate()->find($this->slotId);

            if (! $slot || $slot->status !== ExecutionSlot::STATUS_DELETING || $slot->operation_id !== $this->operationId) {
                return;
            }

            $slot->update([
                'status' => ExecutionSlot::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
            ]);
        });
    }
}
