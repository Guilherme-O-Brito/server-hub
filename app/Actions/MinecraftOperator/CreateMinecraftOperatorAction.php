<?php

namespace App\Actions\MinecraftOperator;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use DB;

class CreateMinecraftOperatorAction
{
    public function execute(MinecraftServer $minecraftServer, array $validated)
    {
        DB::transaction(function () use ($minecraftServer, $validated) {
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

            $server->operators()->create([
                'nickname' => $validated['nickname']
            ]);

            DB::afterCommit(function () use ($server, $generation) {
                UpdateMinecraftInfrastructureJob::dispatch($server->id, $generation);
            });

        });
    }
}