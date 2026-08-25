<?php

namespace App\Actions\MinecraftWhitelist;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use DB;
use Illuminate\Support\Str;

class CreateMinecraftWhitelistAction
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

            $operationId = (string) Str::uuid();

            $server->update([
                'status' => MinecraftServerStatus::Provisioning,
                'operation_id' => $operationId,
                'last_error' => null
            ]);

            $server->whitelist()->create([
                'nickname' => $validated['nickname']
            ]);

            DB::afterCommit(function () use ($server, $operationId) {
                UpdateMinecraftInfrastructureJob::dispatch($server->id, $operationId);
            });
        });
    }
}