<?php

namespace App\Actions\ExecutionSlot;

use App\Exceptions\NoExecutionSlotAvailableException;
use App\Models\ExecutionSlot;
use DB;
use Illuminate\Database\Eloquent\Model;

class AllocateExecutionSlotAction
{
    public function execute(Model $server) // this method must be used inside a db transaction
    {
        
        // find the first free slot
        $slot = ExecutionSlot::where('status', ExecutionSlot::STATUS_FREE)->orderBy('slot_number')->lockForUpdate()->first();

        if (! $slot) {
            throw new NoExecutionSlotAvailableException(
                'No execution slot available.'
            );
        }

        $slot->server()->associate($server);

        $slot->status = ExecutionSlot::STATUS_ALLOCATED;

        $slot->save();

        return $slot;

    }
}