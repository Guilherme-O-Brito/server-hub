<?php

namespace App\Jobs;

use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Services\Kubernetes\ProvisioningService;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StopMinecraftServerJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $serverId,
        public int $slotId
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(ProvisioningService $provisioningService): void
    {
        $minecraftServer = MinecraftServer::findOrFail($this->serverId);
        
        $provisioningService->stopMinecraftServer($minecraftServer);

        DB::transaction(function () use ($minecraftServer){
            $server = MinecraftServer::query()->lockForUpdate()->find($minecraftServer->id);
            $slot = ExecutionSlot::query()->lockForUpdate()->find($this->slotId);
            
            $server->update([
                'status' => MinecraftServerStatus::Stopped,
                'last_error' => null
            ]);
            
            if ($slot) {
                $slot->release();
            }
        });
    }

    public function failed(\Throwable $exception): void
    {
        $server = MinecraftServer::find($this->serverId);
        
        if ($server) {
            $server->update([
                'status' => MinecraftServerStatus::Running,
                'last_error' => $exception->getMessage(),
            ]);
        }

    }
}
