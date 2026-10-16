<?php

namespace App\Actions\MinecraftOperator;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\enums\MinecraftServerStatus;
use App\Models\MinecraftOperator;
use App\Models\MinecraftServer;
use DB;
use Illuminate\Support\Str;

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
            
            $operationId = (string) Str::uuid();

            $server->fill([
                'operation_id' => $operationId,
                'last_error' => null
            ]);
            $server->updateStatus(MinecraftServerStatus::Provisioning);

            DB::afterCommit(function () use ($server, $operationId, $minecraftOperator) {
                $minecraftOperator->delete();
                UpdateMinecraftInfrastructureJob::dispatch($server->id, $operationId);
            });

        });

    }
}