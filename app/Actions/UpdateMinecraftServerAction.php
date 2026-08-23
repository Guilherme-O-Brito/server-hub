<?php

namespace App\Actions;

use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\User;
use DB;

class UpdateMinecraftServerAction
{
    public function execute(User $user, MinecraftServer $minecraftServer, array $data)
    {   
        DB::transaction(function () use ($user, $minecraftServer, $data) {
            $server = MinecraftServer::query()->lockForUpdate()->findOrFail($minecraftServer->id);

            if ($server->status !== MinecraftServerStatus::Stopped) {
                throw new MinecraftServerStateException(
                    'Minecraft server is not stopped.'
                );
            }

            $generation = $server->operation_generation + 1;

            $server->update([
                'server_name' => $data['server_name'],
                'motd' => $data['motd'] ?? "{$user->name}'s minecraft server",
                'minecraft_version_id' => $data['minecraft_version_id'],
                'difficulty' => $data['difficulty'],
                'force_gamemode' => $data['force_gamemode'],
                'allow_flight' => $data['allow_flight'],
                'status' => MinecraftServerStatus::Provisioning,
                'operation_generation' => $generation,
                'last_error' => null
            ]);

            DB::afterCommit(function () use ($server, $generation) {
                UpdateMinecraftInfrastructureJob::dispatch($server->id, $generation);
            });
        });
    }
}