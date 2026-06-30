<?php

namespace App\Actions\MinecraftVersion;

use App\Exceptions\MinecraftVersionDeleteException;
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
                ->where('is_enabled', true)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            
            if (! $replacementVersion) {
                throw new MinecraftVersionDeleteException(
                    'There is no other version enabled to replace this version.'
                );
            }

            MinecraftServer::query()
            ->where('minecraft_version_id', $minecraftVersion->id)
            ->update([
                'minecraft_version_id' => $replacementVersion->id
            ]);

            $minecraftVersion->delete();
        });
    }
}