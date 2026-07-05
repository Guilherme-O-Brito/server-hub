<?php

namespace App\Actions;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\StopMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use DB;

class StopMinecraftServerAction
{
    public function execute(MinecraftServer $minecraftServer)
    {
        DB::transaction(function () use ($minecraftServer){
            $server = MinecraftServer::query()->lockForUpdate()->find($minecraftServer->id);
            if ($server->status !== MinecraftServerStatus::Running) {
                throw new MinecraftServerStateException(
                    'Minecraft server is not running.'
                );
            }

            $slot = $server->executionSlot;

            if (! $slot) {
                throw new MinecraftServerStateException(
                    'Minecraft server has no execution slot.'
                );
            }

            $server->update([
                'status' => MinecraftServerStatus::Stopping
            ]);

            DB::afterCommit(function () use ($server, $slot){
                StopMinecraftServerJob::dispatch($server->id, $slot->id);
            });

        });
    }
}