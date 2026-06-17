<?php

namespace App\Actions;

use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\Models\MinecraftServer;
use App\Models\User;

class UpdateMinecraftServerAction
{
    public function execute(User $user, MinecraftServer $server, array $data)
    {   
        $server->server_name = $data['server_name'];
        $server->motd = $data['motd'] ?? "{$user->name}'s minecraft server";
        $server->difficulty = $data['difficulty'];
        $server->force_gamemode = $data['force_gamemode'];
        $server->allow_flight = $data['allow_flight'];

        $server->save();

        UpdateMinecraftInfrastructureJob::dispatch($server->id);
    }
}