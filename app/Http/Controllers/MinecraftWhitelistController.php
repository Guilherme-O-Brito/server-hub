<?php

namespace App\Http\Controllers;

use App\Models\MinecraftServer;
use App\Models\MinecraftWhitelist;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MinecraftWhitelistController extends Controller
{
    public function index(Request $request, MinecraftServer $minecraftServer)
    {
        if ($request->user()->cannot('manageWhitelist', $minecraftServer)) {
            abort(403);
        }

        $whitelist = $minecraftServer->whitelist()->orderBy('nickname')->get();

        return response()->json($whitelist);
    }

    public function create(Request $request, MinecraftServer $minecraftServer)
    {
        if ($request->user()->cannot('manageWhitelist', $minecraftServer)) {
            abort(403);
        }

        $validated = $request->validate([
            'nickname' => [
                'required',
                'string',
                'max:16',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('minecraft_whitelists', 'nickname')->where(fn ($query) => $query->where('minecraft_server_id', $minecraftServer->id)),
            ]
        ]);

        $minecraftServer->whitelist()->create([
            'nickname' => $validated['nickname']
        ]);

        return response()->json(['message' => 'User added to this minecraft server successfully'], 201);
    }

    public function delete(Request $request, MinecraftServer $minecraftServer, MinecraftWhitelist $minecraftWhitelist)
    {
        if ($request->user()->cannot('manageWhitelist', $minecraftServer)) {
            abort(403);
        }

        if ($minecraftWhitelist->minecraft_server_id !== $minecraftServer->id) {
            abort(404);
        }

        $minecraftWhitelist->delete();

        return response()->json(['message' => 'Nickname successfully deleted from the whitelist']);
    }
}
