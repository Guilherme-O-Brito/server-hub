<?php

namespace App\Actions\MinecraftWhitelist;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\enums\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\MinecraftWhitelist;
use DB;
use Illuminate\Support\Str;



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

            $operationId = (string) Str::uuid();

            $server->fill([
                'operation_id' => $operationId,
                'last_error' => null
            ]);
            $server->updateStatus(MinecraftServerStatus::Provisioning);

            DB::afterCommit(function () use ($server, $operationId, $minecraftWhitelist) {
                $minecraftWhitelist->delete();
                UpdateMinecraftInfrastructureJob::dispatch($server->id, $operationId);    
            });
        });

    }
}