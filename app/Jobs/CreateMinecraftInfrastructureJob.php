<?php

namespace App\Jobs;

use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Services\Kubernetes\ProvisioningService;
use DB;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class CreateMinecraftInfrastructureJob implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $serverId, public string $operationId)
    {
        //
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("minecraft-server:{$this->serverId}"))->shared(),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(ProvisioningService $provisioningService): void
    {
        $server = MinecraftServer::find($this->serverId);

        if (! $server || $server->status !== MinecraftServerStatus::Provisioning || $server->operation_id !== $this->operationId) {
            return;
        }

        $provisioningService->provisionMinecraftServer($server);

        DB::transaction(function () {
            $server = MinecraftServer::query()->lockForUpdate()->find($this->serverId);    

            if (! $server || $server->status !== MinecraftServerStatus::Provisioning || $server->operation_id !== $this->operationId) {
                return;
            }
            
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
    
            if (! $server || $server->status !== MinecraftServerStatus::Provisioning || $server->operation_id !== $this->operationId) {
                return;
            }

            $server->update([
                'status' => MinecraftServerStatus::Failed,
                'last_error' => $exception->getMessage(),
            ]);
        });

    }
}
