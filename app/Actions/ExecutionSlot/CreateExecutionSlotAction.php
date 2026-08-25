<?php 

namespace App\Actions\ExecutionSlot;

use App\Jobs\ExecutionSlot\CreateExecutionSlotServiceJob;
use App\Models\ExecutionSlot;
use DB;
use Illuminate\Support\Str;

class CreateExecutionSlotAction
{
    public function execute()
    {   
        DB::transaction(function () {
            $last_execution_slot = ExecutionSlot::orderByDesc('slot_number')->lockForUpdate()->first();
            $slot_number = ($last_execution_slot?->slot_number + 1) ?? 1;
            $external_port = ($last_execution_slot?->external_port ?? 29999) + 1;
            $service_name = "server-service-{$slot_number}";
            $hostname = ExecutionSlot::generateHostname($slot_number);
            
            $operationId = (string) Str::uuid();

            $executionSlot = ExecutionSlot::create([
                'slot_number' => $slot_number,
                'external_port' => $external_port,
                'service_name' => $service_name,
                'status' => ExecutionSlot::STATUS_PROVISIONING,
                'hostname' => $hostname,
                'operation_id' => $operationId
            ]);

            DB::afterCommit(function () use ($executionSlot, $operationId) {
                CreateExecutionSlotServiceJob::dispatch($executionSlot->id, $operationId);
            });

        }, 3);

    }
}