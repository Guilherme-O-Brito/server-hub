<?php

namespace App\Actions\ExecutionSlot;

use App\Exceptions\ExecutionSlotStateException;
use App\Jobs\ExecutionSlot\DeleteExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use DB;

class DeleteExecutionSlotAction {
    public function execute()
    {
        DB::transaction(function () {
            $slot = ExecutionSlot::orderByDesc('slot_number')->lockForUpdate()->firstOrFail();
            
            if ($slot->isOccupied()) {
                throw new ExecutionSlotStateException('Cannot delete occupied slot');
            }

            $slot->update([
                'status' => ExecutionSlot::STATUS_DELETING
            ]);

            DB::afterCommit(function () use ($slot) {
                DeleteExecutionSlotServiceJob::dispatch($slot->id);
            });
        }, 3);
    }
}