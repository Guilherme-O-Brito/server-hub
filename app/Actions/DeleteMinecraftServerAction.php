<?php

namespace App\Actions;

use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;

class DeleteMinecraftServerAction
{
    public function execute(MinecraftServer $server)
    {
        $server->update([
            'status' => MinecraftServerStatus::Deleting
        ]);

        DeleteMinecraftinfrastructureJob::dispatch($server->id);
    }
}