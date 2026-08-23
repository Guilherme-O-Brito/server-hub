<?php

namespace App\Jobs;

use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Services\Kubernetes\ProvisioningService;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class StartMinecraftServerJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $serverId,
        public int $slotId,
        public int $generation
    )
    {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("minecraft-server:{$this->serverId}"))->shared(),
        ];
    }

    private function getCurrentContext(): ?array
    {
        return DB::transaction(function () {
            $server = MinecraftServer::query()->lockForUpdate()->find($this->serverId);

            if (! $server || $server->status !== MinecraftServerStatus::Starting || $server->operation_generation !== $this->generation) {
                return null;
            }

            $slot = ExecutionSlot::query()->lockForUpdate()->find($this->slotId);

            if (! $slot || ! $slot->isAllocatedTo($server)) {
                return null;
            }

            return [$server, $slot];
        });
    }

    /**
     * Execute the job.
     */
    public function handle(ProvisioningService $provisioningService): void
    {   
        $context = $this->getCurrentContext();

        if (! $context) {
            return;
        }
        
        [$server, $slot] = $context;

        $provisioningService->updateExecutionSlotService($slot);
        $provisioningService->startMinecraftServer($server);

        DB::transaction(function () {
            $server = MinecraftServer::query()->lockForUpdate()->find($this->serverId);

            if (! $server) {
                return;
            }

            if ($server->status !== MinecraftServerStatus::Starting || $server->operation_generation !== $this->generation) {
                return;
            }

            $slot = ExecutionSlot::query()->lockForUpdate()->find($this->slotId);

            if (! $slot || ! $slot->isAllocatedTo($server)) {
                return;
            }

            $server->update([
                'status' => MinecraftServerStatus::Running,
                'last_error' => null
            ]);
        });
    }

    public function failed(\Throwable $exception): void
    {
        DB::transaction(function () use ($exception){
            $server = MinecraftServer::query()->lockForUpdate()->find($this->serverId);

            if (! $server) {
                return;
            }

            if ($server->status !== MinecraftServerStatus::Starting || $server->operation_generation !== $this->generation) {
                return;
            }

            $slot = ExecutionSlot::query()->lockForUpdate()->find($this->slotId);

            if ($slot) {
                $slot->release($server);
            }

            $server->update([
                'status' => MinecraftServerStatus::Failed,
                'last_error' => $exception->getMessage()
            ]);
        });
    }
}
