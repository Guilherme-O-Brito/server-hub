<?php

namespace App\Jobs\ExecutionSlot;

use App\Models\ExecutionSlot;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateExecutionSlotServiceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $slotId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ProvisioningService $provisioningService): void
    {
        $slot = ExecutionSlot::findOrFail($this->slotId);

        $provisioningService->provisionExecutionSlotService($slot);
    }

    public function failed(\Throwable $exception): void
    {
        $slot = ExecutionSlot::find($this->slotId);

        if ($slot) {
            $slot->update([
                'status' => ExecutionSlot::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
            ]);
        }
    }
}
