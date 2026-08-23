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
    public function execute(MinecraftServer $minecraftServer)
    {   
        DB::transaction(function () use ($minecraftServer) {
            $server = MinecraftServer::query()->lockForUpdate()->findOrFail($minecraftServer->id);

            if ($server->status === MinecraftServerStatus::Deleting) {
                throw new MinecraftServerStateException(
                    'Minecraft server is already being deleted.'
                );
            }

            $generation = $server->operation_generation + 1;

            $server->update([
                'status' => MinecraftServerStatus::Deleting,
                'operation_generation' => $generation,
                'last_error' => null
            ]);

            DB::afterCommit(function () use ($server, $generation) {
                DeleteMinecraftinfrastructureJob::dispatch($server->id, $generation);
            });
        });
    }
}