<?php

namespace App\Actions\ExecutionSlot;

use App\Models\ExecutionSlot;
use DB;
use Illuminate\Database\Eloquent\Model;

class AllocateExecutionSlotAction
{
    public function execute(Model $server)
    {
        DB::transaction(function () use ($server) {

            // find the first free slot
            $slot = ExecutionSlot::orderBy('slot_number')->lockForUpdate()->where('status', ExecutionSlot::STATUS_FREE)->firstOrFail();

            $slot->server()->associate($server);

            $slot->status = ExecutionSlot::STATUS_ALLOCATED;

            $slot->save();

        });
    }
}