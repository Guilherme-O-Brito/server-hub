<?php

namespace App\Http\Controllers;

use App\Actions\CreateMinecraftServerAction;
use App\Actions\DeleteMinecraftServerAction;
use App\Actions\UpdateMinecraftServerAction;
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

}
