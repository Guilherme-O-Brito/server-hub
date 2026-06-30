<?php

namespace App\Actions;

use App\Jobs\CreateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\User;
use App\Models\MinecraftServer;

class CreateMinecraftServerAction
{
    public function execute(User $user, array $data)
    {
        $motd = $data['motd'] ?? "{$user->name}'s minecraft server";

        $force_gamemode = $data['force_gamemode'];

        $allow_flight = $data['allow_flight'];

        $server = $user->ownedMinecraftServers()->create([
            'server_name' => $data['server_name'],
            'motd' => $motd,
            'difficulty' => $data['difficulty'],
            'minecraft_version_id' => $data['minecraft_version_id'],
            'force_gamemode' => $force_gamemode,
            'allow_flight' => $allow_flight,
            'status' => MinecraftServerStatus::Provisioning
        ]);

        CreateMinecraftInfrastructureJob::dispatch($server->id);
    }
}
