<?php

namespace App\Actions\ExecutionSlot;

use App\Exceptions\ExecutionSlotStateException;
use App\Jobs\ExecutionSlot\DeleteExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use DB;
use Illuminate\Support\Str;

class DeleteExecutionSlotAction {
    public function execute()
    {
        DB::transaction(function () {
            $slot = ExecutionSlot::orderByDesc('slot_number')->lockForUpdate()->firstOrFail();
            
            if ($slot->isOccupied()) {
                throw new ExecutionSlotStateException('Cannot delete occupied slot');
            }

            $operationId = (string) Str::uuid();

            $slot->update([
                'status' => ExecutionSlot::STATUS_DELETING,
                'operation_id' => $operationId,
                'last_error' => null
            ]);

            DB::afterCommit(function () use ($slot, $operationId) {
                DeleteExecutionSlotServiceJob::dispatch($slot->id, $operationId);
            });
        }, 3);
    }
}