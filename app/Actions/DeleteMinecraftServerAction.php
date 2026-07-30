<?php

namespace App\Actions;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use DB;

class DeleteMinecraftServerAction
{
    public function execute(MinecraftServer $server)
    {   
        /*if ($server->status !== MinecraftServerStatus::Stopped) {
            throw new MinecraftServerStateException(
                'Minecraft server is not stopped.'
            );
        }*/

        $server->update([
            'status' => MinecraftServerStatus::Deleting
        ]);

        $serverSlot = $server->executionSlot;

        if ($serverSlot) {
            DB::transaction(function () use ($serverSlot) {
                $slot = ExecutionSlot::query()->lockForUpdate()->find($serverSlot->id);
                $slot->release();
            });
        }

        DeleteMinecraftinfrastructureJob::dispatch($server->id);
    }
}