<?php

namespace App\Http\Controllers;

use App\Actions\MinecraftOperator\CreateMinecraftOperatorAction;
use App\Actions\MinecraftOperator\DeleteMinecraftOperatorAction;
use App\Exceptions\MinecraftServerStateException;
use App\Models\MinecraftOperator;
use App\Models\MinecraftServer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MinecraftOperatorController extends Controller
{
    public function index(Request $request, MinecraftServer $minecraftServer)
    {
        if ($request->user()->cannot('view', $minecraftServer)) {
            abort(403);
        }

        $operators = $minecraftServer->operators()->orderBy('nickname')->get();

        return response()->json($operators);
    }

    public function create(Request $request, MinecraftServer $minecraftServer, CreateMinecraftOperatorAction $action)
    {
        if ($request->user()->cannot('manageOperators', $minecraftServer)) {
            abort(403);
        }

        $validated = $request->validate([
            'nickname' => [
                'required',
                'string',
                'max:16',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('minecraft_operators', 'nickname')->where(fn ($query) => $query->where('minecraft_server_id', $minecraftServer->id)),
            ]
        ]);

        try {
            $action->execute($minecraftServer, $validated);
        } catch (MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }

        return response()->json(['message' => 'User added as operator to this minecraft server successfully'], 201);
    }

    public function delete(Request $request, MinecraftServer $minecraftServer, MinecraftOperator $minecraftOperator, DeleteMinecraftOperatorAction $action)
    {
        if ($request->user()->cannot('manageOperators', $minecraftServer)) {
            abort(403);
        }

        if ($minecraftOperator->minecraft_server_id !== $minecraftServer->id) {
            abort(404);
        }

        try {
            $action->execute($minecraftServer, $minecraftOperator);
        } catch (MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }

        return response()->json(['message' => 'Nickname successfully deleted from the operators in this server']);
    }
}
