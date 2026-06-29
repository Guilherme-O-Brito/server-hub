<?php

namespace App\Http\Controllers;

use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Http\Request;

class MinecraftServerAdminController extends Controller
{
    public function index(Request $request, MinecraftServer $minecraftServer)
    {
        if ($request->user()->cannot('update', $minecraftServer)) {
            abort(403);
        }

        $admins = $minecraftServer->admins()->orderBy('name')->get();

        return response()->json($admins);
    }

    public function store(Request $request, MinecraftServer $minecraftServer, User $user)
    {
        if ($request->user()->cannot('update', $minecraftServer)) {
            abort(403);
        }

        if ($minecraftServer->owner_id === $user->id) {
            return response()->json(['message' => 'Owner is already the owner.'], 422);
        }

        $minecraftServer->admins()->syncWithoutDetaching([$user->id]);

        return response()->json(['message' => 'Admin added successfully.'], 201);

    }

    public function delete(Request $request, MinecraftServer $minecraftServer, User $user)
    {
        if ($request->user()->cannot('update', $minecraftServer)) {
            abort(403);
        }

        $detached = $minecraftServer->admins()->detach($user->id);

        if ($detached === 0) {
            return response()->json(['message' => 'User is not an admin.'], 404);
        }

        return response()->json(['message' => 'Admin removed successfully.']);
    }

}
