<?php

namespace App\Http\Controllers;

use App\Actions\MinecraftConsole\SendMinecraftServerCommandAction;
use App\Exceptions\MinecraftServerStateException;
use App\Http\Requests\MinecraftCommandRequest;
use App\Models\MinecraftServer;
use Exception;
use Illuminate\Http\Request;

class MinecraftConsoleController extends Controller
{
    public function sendCommand(MinecraftCommandRequest $request, MinecraftServer $minecraftServer, SendMinecraftServerCommandAction $action)
    {

        if ($request->user()->cannot('useConsole', $minecraftServer)) {
            abort(403);
        }

        try{
            $console_response = $action->execute($minecraftServer, $request->validated('command'));
        } catch (MinecraftServerStateException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        } catch (Exception $e) {
            abort(502);
        }

        return response()->json(['response' => $console_response]);
    }
}
