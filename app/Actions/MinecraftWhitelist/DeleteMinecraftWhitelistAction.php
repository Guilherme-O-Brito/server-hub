<?php

namespace App\Actions\MinecraftWhitelist;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\MinecraftWhitelist;
use DB;



class DeleteMinecraftWhitelistAction
{
    public function execute(MinecraftServer $minecraftServer, MinecraftWhitelist $minecraftWhitelist)
    {   
        DB::transaction(function () use ($minecraftServer, $minecraftWhitelist) {
            $server = MinecraftServer::query()->lockForUpdate()->findOrFail($minecraftServer->id);    

            if ($server->status !== MinecraftServerStatus::Stopped) {
                throw new MinecraftServerStateException(
                    'Minecraft server is not stopped.'
                );
            }

            $generation = $server->operation_generation + 1;

            $server->update([
                'status' => MinecraftServerStatus::Provisioning,
                'operation_generation' => $generation,
                'last_error' => null
            ]);

            DB::afterCommit(function () use ($server, $generation, $minecraftWhitelist) {
                $minecraftWhitelist->delete();
                UpdateMinecraftInfrastructureJob::dispatch($server->id, $generation);    
            });
        });

    }
}