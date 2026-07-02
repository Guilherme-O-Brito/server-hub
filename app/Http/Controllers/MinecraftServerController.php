<?php

namespace App\Http\Controllers;

use App\Actions\CreateMinecraftServerAction;
use App\Actions\DeleteMinecraftServerAction;
use App\Actions\StartMinecraftServerAction;
use App\Actions\StopMinecraftServerAction;
use App\Actions\UpdateMinecraftServerAction;
use App\Exceptions\MinecraftServerStateException;
use App\Exceptions\NoExecutionSlotAvailableException;
use App\Http\Requests\MinecraftServerRequest;
use App\Models\MinecraftServer;
use Illuminate\Http\Request;

class MinecraftServerController extends Controller
{   
    public function index(Request $request)
    {
        $servers = MinecraftServer::query()
            ->visibleToUser($request->user())
            ->with([
                'version',
                'executionSlot'
            ])->get();
        
        return response()->json([$servers]);
    }

    public function get(Request $request, MinecraftServer $minecraftServer)
    {
        if ($request->user()->cannot('view', $minecraftServer)) {
            abort(403);
        }

        $minecraftServer->load([
            'version',
            'executionSlot',
        ]);

        return response()->json($minecraftServer);
    }

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
        try {
            $action->execute($user, $minecraftServer, $validated);
        } catch (MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }
        
        return response()->json(['message' => 'Minecraft server successfully modified']);
    }

    public function delete(Request $request, MinecraftServer $minecraftServer, DeleteMinecraftServerAction $action) 
    {
        if ($request->user()->cannot('delete', $minecraftServer)) {
            abort(403);
        }
        try {
            $action->execute($minecraftServer);
        } catch (MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }

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

    public function stop(Request $request, MinecraftServer $minecraftServer, StopMinecraftServerAction $action)
    {
        if ($request->user()->cannot('stop', $minecraftServer)) {
            abort(403);
        }

        try {
            $action->execute($minecraftServer);
            return response()->json(['message' => 'Minecraft server is stopping']);
        } catch (MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }
    }

}
