<?php

namespace App\Jobs;

use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Services\Kubernetes\ProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteMinecraftinfrastructureJob implements ShouldQueue
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

        $provisioningService->deleteMinecraftServer($server);

        $server->delete();
    }

    public function failed(\Throwable $exception): void
    {
        $server = MinecraftServer::find($this->serverId);

        if ($server) {
            $server->update([
                'status' => MinecraftServerStatus::DeleteFailed,
                'last_error' => $exception->getMessage(),
            ]);
        }
    }
}
