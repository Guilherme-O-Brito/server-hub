<?php

namespace App\Jobs;

use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Services\Kubernetes\ProvisioningService;
use DB;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class StopMinecraftServerJob implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $serverId,
        public int $slotId,
        public string $operationId
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

            if (! $server || $server->status !== MinecraftServerStatus::Stopping || $server->operation_id !== $this->operationId) {
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

        [$server] = $context;
        
        $provisioningService->stopMinecraftServer($server);

        DB::transaction(function () {
            $server = MinecraftServer::query()->lockForUpdate()->find($this->serverId);
            
            if (! $server || $server->status !== MinecraftServerStatus::Stopping || $server->operation_id !== $this->operationId) {
                return;
            }

            $slot = ExecutionSlot::query()->lockForUpdate()->find($this->slotId);

            if (! $slot || ! $slot->isAllocatedTo($server)) {
                return;
            }
            
            $slot->release($server);

            $server->update([
                'status' => MinecraftServerStatus::Stopped,
                'last_error' => null
            ]);
            
        });
    }

    public function failed(\Throwable $exception): void
    {
        DB::transaction(function () use ($exception) {
            $server = MinecraftServer::query()->lockForUpdate()->find($this->serverId);
            
            if (! $server || $server->status !== MinecraftServerStatus::Stopping || $server->operation_id !== $this->operationId) {
                return;
            }
            
            $server->update([
                'status' => MinecraftServerStatus::Failed,
                'last_error' => $exception->getMessage()
            ]);
        });
    }
}
