<?php

namespace App\Actions\MinecraftConsole;

use App\enums\MinecraftServerStatus;
use App\Exceptions\MinecraftServerStateException;
use App\Models\MinecraftServer;
use App\Services\Minecraft\MinecraftRconService;
use Exception;

class SendMinecraftServerCommandAction {

    public function __construct(
        private MinecraftRconService $minecraftRconService,
    )
    {}

    public function execute(MinecraftServer $server, string $command): string
    {   
        $server->refresh();
        if ($server->status !== MinecraftServerStatus::Running) {
            throw new MinecraftServerStateException(
                'Minecraft server is not running.'
            );
        }

        $minecraft_rcon_host = $server->getServiceName();
        
        $response = $this->minecraftRconService->execute($minecraft_rcon_host, $command);
        
        return $response;
    }
}