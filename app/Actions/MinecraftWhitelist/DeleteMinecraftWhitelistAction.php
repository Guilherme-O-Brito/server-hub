<?php

namespace App\Actions\MinecraftWhitelist;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\MinecraftWhitelist;



class DeleteMinecraftWhitelistAction
{
    public function execute(MinecraftServer $minecraftServer, MinecraftWhitelist $minecraftWhitelist)
    {
        if ($minecraftServer->status !== MinecraftServerStatus::Stopped) {
            throw new MinecraftServerStateException(
                'Minecraft server is not stopped.'
            );
        }

        $minecraftWhitelist->delete();

        UpdateMinecraftInfrastructureJob::dispatch($minecraftServer->id);
    }
}