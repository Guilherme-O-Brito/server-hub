<?php

namespace App\Http\Controllers;

use App\Models\MinecraftServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MinecraftServerController extends Controller
{   
    public function create(Request $request)
    {
        $validated = $request->validate([
            'server_name' => ['required', 'string', 'max:255'],
            'motd' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['required', 'integer', 'min:0', 'max:3'],  
            'force_gamemode' => ['boolean'],
            'allow_flight' => ['boolean']    
        ]);

        $user = auth()->user();

        $motd = $validated['motd'] ?? "{$user->name}'s minecraft server";

        $force_gamemode = $validated['force_gamemode'] ?? true;

        $allow_flight = $validated['allow_flight'] ?? true;

        $user->ownedMinecraftServers()->create([
            'server_name' => $validated['server_name'],
            'motd' => $motd,
            'difficulty' => $validated['difficulty'],
            'force_gamemode' => $force_gamemode,
            'allow_flight' => $allow_flight
        ]);
        
        return response()->json(['message' => 'Minecraft server created successfully'], 201);

    }

    public function update(Request $request, MinecraftServer $minecraftServer)
    {
        if ($request->user()->cannot('update', $minecraftServer)) {
            abort(403);
        }

        $validated = $request->validate([
            'server_name' => ['required', 'string', 'max:255'],
            'motd' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['required', 'integer', 'min:0', 'max:3'],  
            'force_gamemode' => ['boolean'],
            'allow_flight' => ['boolean']    
        ]);

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
