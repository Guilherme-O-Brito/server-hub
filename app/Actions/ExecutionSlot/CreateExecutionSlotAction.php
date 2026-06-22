<?php 

namespace App\Actions\ExecutionSlot;

use App\Jobs\ExecutionSlot\CreateExecutionSlotServiceJob;
use App\Models\ExecutionSlot;

class CreateExecutionSlotAction
{
    public function execute()
    {
        $last_execution_slot = ExecutionSlot::orderByDesc('slot_number')->first();
        $slot_number = ($last_execution_slot?->slot_number + 1) ?? 1;
        $external_port = ($last_execution_slot?->external_port ?? 29999) + 1;
        $service_name = "server-service-{$slot_number}";

        $executionSlot = ExecutionSlot::create([
            'slot_number' => $slot_number,
            'external_port' => $external_port,
            'service_name' => $service_name,
            'status' => ExecutionSlot::STATUS_PROVISIONING,
        ]);

        CreateExecutionSlotServiceJob::dispatch($executionSlot->id);
    }
}