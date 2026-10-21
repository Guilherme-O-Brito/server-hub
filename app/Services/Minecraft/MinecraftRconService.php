<?php

namespace App\Services\Minecraft;

use Exception;
use xPaw\SourceQuery\SourceQuery;

class MinecraftRconService
{
    public function execute(string $host, string $command): string
    {
        $client = new SourceQuery();

        try {
            $client->Connect($host, 25575, 3);
            $client->SetRconPassword(config('minecraftrcon.password'));

            return $client->Rcon($command);
        } finally {
            $client->Disconnect();
        }
    }
}
