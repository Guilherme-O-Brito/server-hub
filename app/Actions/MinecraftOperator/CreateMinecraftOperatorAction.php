<?php

namespace App\Actions\MinecraftOperator;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;

class CreateMinecraftOperatorAction
{
    public function execute(MinecraftServer $minecraftServer, array $validated)
    {
        if ($minecraftServer->status !== MinecraftServerStatus::Stopped) {
            throw new MinecraftServerStateException(
                'Minecraft server is not stopped.'
            );
        }

        $minecraftServer->operators()->create([
            'nickname' => $validated['nickname']
        ]);

        UpdateMinecraftInfrastructureJob::dispatch($minecraftServer->id);
    }
}