<?php

namespace App\Actions;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;

class DeleteMinecraftServerAction
{
    public function execute(MinecraftServer $server)
    {   
        if ($server->status !== MinecraftServerStatus::Stopped) {
            throw new MinecraftServerStateException(
                'Minecraft server is not stopped.'
            );
        }

        $server->update([
            'status' => MinecraftServerStatus::Deleting
        ]);

        DeleteMinecraftinfrastructureJob::dispatch($server->id);
    }
}