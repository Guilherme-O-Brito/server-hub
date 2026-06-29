<?php

namespace App\Actions\MinecraftWhitelist;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;

class CreateMinecraftWhitelistAction
{
    public function execute(MinecraftServer $minecraftServer, array $validated)
    {
        if ($minecraftServer->status !== MinecraftServerStatus::Stopped) {
            throw new MinecraftServerStateException(
                'Minecraft server is not stopped.'
            );
        }

        $minecraftServer->whitelist()->create([
            'nickname' => $validated['nickname']
        ]);

        UpdateMinecraftInfrastructureJob::dispatch($minecraftServer->id);
    }
}