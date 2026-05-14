<?php

namespace App\Http\Controllers;

use App\Models\MinecraftServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MinecraftServerController extends Controller
{   
    public function create(Request $request)
    {
        $validated = $request->validate([
            'server_name' => ['required', 'string', 'max:255'],
            'level_name' => ['required', 'string', 'max:255'],
            'motd' => ['string', 'max:255'],
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
            'level_name' => $validated['level_name'],
            'motd' => $motd,
            'difficulty' => $validated['difficulty'],
            'force_gamemode' => $force_gamemode,
            'allow_flight' => $allow_flight
        ]);
        
        return response()->json(['message' => 'Minecraft server created successfully'], 201);

    }
}
