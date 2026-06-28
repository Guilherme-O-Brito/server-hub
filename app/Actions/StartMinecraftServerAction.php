<?php 

namespace App\Actions;

use App\Actions\ExecutionSlot\AllocateExecutionSlotAction;
use App\Exceptions\MinecraftServerStateException;
use App\Jobs\StartMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use DB;

class StartMinecraftServerAction
{
    public function __construct(
        private AllocateExecutionSlotAction $allocateExecutionSlotAction,
    )
    {}

    public function execute(MinecraftServer $minecraftServer) 
    {
        DB::transaction(function () use ($minecraftServer) {
            $server = MinecraftServer::query()->lockForUpdate()->findOrFail($minecraftServer->id);

            if ($server->status !== MinecraftServerStatus::Stopped) {
                throw new MinecraftServerStateException(
                    'Minecraft server is not stopped.'
                );
            }
    
            $slot = $this->allocateExecutionSlotAction->execute($server);

            $server->status = MinecraftServerStatus::Starting;
            $server->save();

            DB::afterCommit(function () use ($server, $slot) {
                StartMinecraftServerJob::dispatch($server->id, $slot->id);
            });
        });

    }
}