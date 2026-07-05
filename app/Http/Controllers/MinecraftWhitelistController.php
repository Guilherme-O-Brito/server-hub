<?php

namespace App\Http\Controllers;

use App\Actions\MinecraftWhitelist\CreateMinecraftWhitelistAction;
use App\Actions\MinecraftWhitelist\DeleteMinecraftWhitelistAction;
use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
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

    public function create(Request $request, MinecraftServer $minecraftServer, CreateMinecraftWhitelistAction $action)
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

        try {
            $action->execute($minecraftServer, $validated);
        } catch (MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }

        return response()->json(['message' => 'User added to this minecraft server successfully'], 201);
    }

    public function delete(Request $request, MinecraftServer $minecraftServer, MinecraftWhitelist $minecraftWhitelist, DeleteMinecraftWhitelistAction $action)
    {
        if ($request->user()->cannot('manageWhitelist', $minecraftServer)) {
            abort(403);
        }

        if ($minecraftWhitelist->minecraft_server_id !== $minecraftServer->id) {
            abort(404);
        }

        try {
            $action->execute($minecraftServer, $minecraftWhitelist);
        } catch (MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }

        return response()->json(['message' => 'Nickname successfully deleted from the whitelist']);
    }
}
