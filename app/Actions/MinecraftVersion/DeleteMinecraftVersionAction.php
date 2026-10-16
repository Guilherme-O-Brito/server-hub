<?php

namespace App\Actions\MinecraftVersion;

use App\Exceptions\MinecraftVersionDeleteException;
use App\Events\MinecraftServerUpdated;
use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use DB;

class DeleteMinecraftVersionAction
{
    public function execute(MinecraftVersion $minecraftVersion)
    {
        DB::transaction(function () use ($minecraftVersion) {
            $replacementVersion = MinecraftVersion::query()
                ->whereKeyNot($minecraftVersion->id)
                ->enabled()
                ->orderedDesc()
                ->lockForUpdate()
                ->first();
            
            if (! $replacementVersion) {
                throw new MinecraftVersionDeleteException(
                    'There is no other version enabled to replace this version.'
                );
            }

            $serverIds = MinecraftServer::query()
                ->where('minecraft_version_id', $minecraftVersion->id)
                ->pluck('id');

            MinecraftServer::query()
                ->where('minecraft_version_id', $minecraftVersion->id)
                ->update([
                    'minecraft_version_id' => $replacementVersion->id
                ]);

            MinecraftServer::query()->whereIn('id', $serverIds)->get()->each(
                fn (MinecraftServer $server) => MinecraftServerUpdated::dispatch($server)
            );

            $minecraftVersion->delete();
        });
    }
}
