<?php

namespace App\Actions\MinecraftOperator;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftOperator;
use App\Models\MinecraftServer;
use DB;

class DeleteMinecraftOperatorAction
{
    public function execute(MinecraftServer $minecraftServer, MinecraftOperator $minecraftOperator)
    {   
        DB::transaction(function () use ($minecraftServer, $minecraftOperator) {
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

            DB::afterCommit(function () use ($server, $generation, $minecraftOperator) {
                $minecraftOperator->delete();
                UpdateMinecraftInfrastructureJob::dispatch($server->id, $generation);
            });

        });

    }
}