<?php

namespace App\Actions;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\StopMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use DB;

class StopMinecraftServerAction
{
    public function execute(MinecraftServer $minecraftServer)
    {
        DB::transaction(function () use ($minecraftServer){
            $server = MinecraftServer::query()->lockForUpdate()->findOrFail($minecraftServer->id);
            if ($server->status !== MinecraftServerStatus::Running) {
                throw new MinecraftServerStateException(
                    'Minecraft server is not running.'
                );
            }

            $slot = ExecutionSlot::query()
                ->where('server_type', $server->getMorphClass())
                ->where('server_id', $server->id)
                ->lockForUpdate()
                ->first();

            if (! $slot) {
                throw new MinecraftServerStateException(
                    'Minecraft server has no execution slot.'
                );
            }

            $generation = $server->operation_generation + 1;

            $server->update([
                'status' => MinecraftServerStatus::Stopping,
                'operation_generation' => $generation,
                'last_error' => null
            ]);

            DB::afterCommit(function () use ($server, $slot, $generation){
                StopMinecraftServerJob::dispatch($server->id, $slot->id, $generation);
            });

        });
    }
}