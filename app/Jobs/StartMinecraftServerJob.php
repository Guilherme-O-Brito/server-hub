<?php

namespace App\Jobs;

use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Services\Kubernetes\ProvisioningService;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StartMinecraftServerJob implements ShouldQueue
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
        $server = MinecraftServer::findOrFail($this->serverId);
        $slot = ExecutionSlot::findOrFail($this->slotId);

        $provisioningService->updateExecutionSlotService($slot);
        $provisioningService->startMinecraftServer($server);

        $server->update([
            'status' => MinecraftServerStatus::Running,
            'last_error' => null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $server = MinecraftServer::find($this->serverId);
        
        if ($server) {
            $server->update([
                'status' => MinecraftServerStatus::Stopped,
                'last_error' => $exception->getMessage(),
            ]);
        }

        DB::transaction(function () {
            $slot = ExecutionSlot::query()->lockForUpdate()->find($this->slotId);
            if ($slot) {
                $slot->release();
            }
        });

    }
}
