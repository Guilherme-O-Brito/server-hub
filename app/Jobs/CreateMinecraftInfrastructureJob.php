<?php

namespace App\Jobs;

use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateMinecraftInfrastructureJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $serverId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ProvisioningService $provisioningService): void
    {
        $server = MinecraftServer::findOrFail($this->serverId);

        $provisioningService->provisionMinecraftServer($server);
    }

    public function failed(\Throwable $exception): void
    {
        $server = MinecraftServer::find($this->serverId);

        if ($server) {
            $server->update([
                'status' => MinecraftServerStatus::Failed,
                'last_error' => $exception->getMessage(),
            ]);
        }
    }
}
