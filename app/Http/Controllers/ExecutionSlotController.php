<?php

namespace App\Http\Controllers;

use App\Actions\ExecutionSlot\CreateExecutionSlotAction;
use App\Actions\ExecutionSlot\DeleteExecutionSlotAction;
use App\Exceptions\ExecutionSlotStateException;
use App\Models\ExecutionSlot;
use Illuminate\Http\Request;

class ExecutionSlotController extends Controller
{
    public function index(Request $request)
    {
        $execution_slots = ExecutionSlot::all();

        return response()->json($execution_slots);
    }

    public function create_one(Request $request, CreateExecutionSlotAction $action)
    {   
        $action->execute();

        return response()->json(['message' => 'Execution slot created successfully'], 201);
    }

    public function delete_last(Request $request, DeleteExecutionSlotAction $action)
    {   
        
        try {
            $action->execute();
        } catch (ExecutionSlotStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }

        return response()->json(['message' => 'Execution slot successfully deleted']);
    }
}
