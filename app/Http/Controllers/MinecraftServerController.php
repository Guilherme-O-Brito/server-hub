<?php

namespace App\Http\Controllers;

use App\Actions\CreateMinecraftServerAction;
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

    public function update(MinecraftServerRequest $request, MinecraftServer $minecraftServer)
    {
        if ($request->user()->cannot('update', $minecraftServer)) {
            abort(403);
        }

        $validated = $request->validated();

        $user = $request->user();

        $minecraftServer->server_name = $validated['server_name'];
        $minecraftServer->motd = $validated['motd'] ?? "{$user->name}'s minecraft server";
        $minecraftServer->difficulty = $validated['difficulty'];
        $minecraftServer->force_gamemode = $validated['force_gamemode'] ?? true;
        $minecraftServer->allow_flight = $validated['allow_flight'] ?? true;

        $minecraftServer->save();
        
        return response()->json(['message' => 'Minecraft server successfully modified']);
    }

    public function delete(Request $request, MinecraftServer $minecraftServer) 
    {
        if ($request->user()->cannot('delete', $minecraftServer)) {
            abort(403);
        }

        $minecraftServer->delete();

        return response()->json(['message' => 'Server successfully deleted']);
    }

}
