<?php

namespace App\Actions;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use DB;
use Illuminate\Support\Str;

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

            $operationId = (string) Str::uuid();

            $server->update([
                'status' => MinecraftServerStatus::Deleting,
                'operation_id' => $operationId,
                'last_error' => null
            ]);

            DB::afterCommit(function () use ($server, $operationId) {
                DeleteMinecraftinfrastructureJob::dispatch($server->id, $operationId);
            });
        });
    }
}