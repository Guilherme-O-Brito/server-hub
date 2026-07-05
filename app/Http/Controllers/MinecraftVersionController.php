<?php

namespace App\Http\Controllers;

use App\Actions\MinecraftVersion\DeleteMinecraftVersionAction;
use App\Exceptions\MinecraftVersionDeleteException;
use App\Models\MinecraftVersion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MinecraftVersionController extends Controller
{
    public function index(Request $request)
    {
        $versions = MinecraftVersion::query()->enabled()->orderedDesc()->get();

        return response()->json($versions);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:8', 'regex:/^\d+(\.\d+){1,2}$/', Rule::unique('minecraft_versions', 'version'),],
            'is_enabled' => ['required', 'boolean']
        ]);

        MinecraftVersion::create([
            'version' => $validated['version'],
            'is_enabled' => $validated['is_enabled']
        ]);

        return response()->json(['message' => 'Minecraft version created.'], 201);
    }

    public function toggle(Request $request, MinecraftVersion $minecraftVersion)
    {
        $minecraftVersion->is_enabled = !$minecraftVersion->is_enabled;

        $minecraftVersion->save();
        
        return response()->json(['message' => 'Minecraft version toggled.']);
    }

    public function delete(Request $request, MinecraftVersion $minecraftVersion, DeleteMinecraftVersionAction $action)
    {
        try {
            $action->execute($minecraftVersion);
        } catch (MinecraftVersionDeleteException $e) {
            return response()->json(['message' => $e->getMessage()], $e->statusCode());
        }

        return response()->json(['message' => 'Minecraft version deleted.']);
    }

}
