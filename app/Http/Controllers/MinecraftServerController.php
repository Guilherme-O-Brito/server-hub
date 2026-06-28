<?php

namespace App\Http\Controllers;

use App\Actions\CreateMinecraftServerAction;
use App\Actions\DeleteMinecraftServerAction;
use App\Actions\StartMinecraftServerAction;
use App\Actions\UpdateMinecraftServerAction;
use App\Exceptions\MinecraftServerStateException;
use App\Exceptions\NoExecutionSlotAvailableException;
use App\Http\Requests\MinecraftServerRequest;
use App\Models\MinecraftServer;
use Illuminate\Http\Request;

class MinecraftServerController extends Controller
{   
    public function create(
        MinecraftServerRequest $request,
        CreateMinecraftServerAction $action
    ) {
        $validated = $request->validated();

        $user = auth()->user();

        $action->execute($user, $validated);
        
        return response()->json(['message' => 'Minecraft server created successfully'], 201);

    }

    public function update(MinecraftServerRequest $request, MinecraftServer $minecraftServer, UpdateMinecraftServerAction $action)
    {
        if ($request->user()->cannot('update', $minecraftServer)) {
            abort(403);
        }

        $validated = $request->validated();

        $user = $request->user();

        $action->execute($user, $minecraftServer, $validated);
        
        return response()->json(['message' => 'Minecraft server successfully modified']);
    }

    public function delete(Request $request, MinecraftServer $minecraftServer, DeleteMinecraftServerAction $action) 
    {
        if ($request->user()->cannot('delete', $minecraftServer)) {
            abort(403);
        }

        $action->execute($minecraftServer);

        return response()->json(['message' => 'Server successfully deleted']);
    }

    public function start(Request $request, MinecraftServer $minecraftServer, StartMinecraftServerAction $action)
    {
        if ($request->user()->cannot('start', $minecraftServer)) {
            abort(403);
        }

        try {
            $action->execute($minecraftServer);
            return response()->json(['message' => 'Minecraft server is starting']);
        } catch (NoExecutionSlotAvailableException|MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }

    }

}
