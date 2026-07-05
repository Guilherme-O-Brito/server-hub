<?php

namespace App\Actions\MinecraftOperator;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftOperator;
use App\Models\MinecraftServer;

class DeleteMinecraftOperatorAction
{
    public function execute(MinecraftServer $minecraftServer, MinecraftOperator $minecraftOperator)
    {
        if ($minecraftServer->status !== MinecraftServerStatus::Stopped) {
            throw new MinecraftServerStateException(
                'Minecraft server is not stopped.'
            );
        }

        $minecraftOperator->delete();

        UpdateMinecraftInfrastructureJob::dispatch($minecraftServer->id);
    }
}