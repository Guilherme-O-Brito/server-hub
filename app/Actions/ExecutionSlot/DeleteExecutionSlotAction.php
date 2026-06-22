<?php

namespace App\Actions\ExecutionSlot;

use App\Jobs\ExecutionSlot\DeleteExecutionSlotServiceJob;
use App\Models\ExecutionSlot;

class DeleteExecutionSlotAction {
    public function execute(ExecutionSlot $slot)
    {
        $slot->update([
            'status' => ExecutionSlot::STATUS_DELETING
        ]);

        DeleteExecutionSlotServiceJob::dispatch($slot->id);
    }
}