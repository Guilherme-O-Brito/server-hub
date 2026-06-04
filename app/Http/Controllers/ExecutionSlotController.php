<?php

namespace App\Http\Controllers;

use App\Models\ExecutionSlot;
use Illuminate\Http\Request;

class ExecutionSlotController extends Controller
{
    public function index(Request $request)
    {
        $execution_slots = ExecutionSlot::all();

        return response()->json($execution_slots);
    }

    public function create_one(Request $request)
    {   
        $last_execution_slot = ExecutionSlot::orderByDesc('slot_number')->first();
        $slot_number = ($last_execution_slot?->slot_number + 1) ?? 1;
        $external_port = ($last_execution_slot?->external_port ?? 29999) + 1;
        $service_name = 'server-service-'.$slot_number;

        ExecutionSlot::create([
            'slot_number' => $slot_number,
            'external_port' => $external_port,
            'service_name' => $service_name,
            'status' => ExecutionSlot::STATUS_STOPPED
        ]);

        return response()->json(['message' => 'Execution slot created successfully'], 201);
    }

    public function delete_last(Request $request)
    {
        $last_execution_slot = ExecutionSlot::orderByDesc('slot_number')->firstOrFail();
        
        if ($last_execution_slot->isOccupied()) {
            return response()->json(['message' => 'Cannot delete occupied slot'], 409);
        }

        $last_execution_slot->delete();

        return response()->json(['message' => 'Execution slot successfully deleted']);
    }
}
