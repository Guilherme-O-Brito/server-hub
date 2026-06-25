<?php 

namespace App\Actions\ExecutionSlot;

use App\Jobs\ExecutionSlot\CreateExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use DB;

class CreateExecutionSlotAction
{
    public function execute()
    {   
        DB::transaction(function () {
            $last_execution_slot = ExecutionSlot::orderByDesc('slot_number')->lockForUpdate()->first();
            $slot_number = ($last_execution_slot?->slot_number + 1) ?? 1;
            $external_port = ($last_execution_slot?->external_port ?? 29999) + 1;
            $service_name = "server-service-{$slot_number}";
    
            $executionSlot = ExecutionSlot::create([
                'slot_number' => $slot_number,
                'external_port' => $external_port,
                'service_name' => $service_name,
                'status' => ExecutionSlot::STATUS_PROVISIONING,
            ]);

            DB::afterCommit(function () use ($executionSlot) {
                CreateExecutionSlotServiceJob::dispatch($executionSlot->id);
            });

        }, 3);

    }
}