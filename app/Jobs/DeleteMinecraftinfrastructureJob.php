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

class DeleteMinecraftinfrastructureJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $serverId, public int $generation)
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

        if (! $server || $server->status !== MinecraftServerStatus::Deleting || $server->operation_generation !== $this->generation) {
            return;
        }

        $provisioningService->deleteMinecraftServer($server);

        DB::transaction(function () {
            $server = MinecraftServer::query()->lockForUpdate()->find($this->serverId);

            if (! $server || $server->status !== MinecraftServerStatus::Deleting || $server->operation_generation !== $this->generation) {
                return;
            }

            $slot = ExecutionSlot::query()
                ->where('server_type', $server->getMorphClass())
                ->where('server_id', $server->id)
                ->first();

            if ($slot) {
                $slot->release($server);
            }
            
            $server->delete();
        });
    }

    public function failed(\Throwable $exception): void
    {
        
        DB::transaction(function () use ($exception) {
            $server = MinecraftServer::query()->lockForUpdate()->find($this->serverId);

            if (! $server || $server->status !== MinecraftServerStatus::Deleting || $server->operation_generation !== $this->generation) {
                return;
            }

            $server->update([
                'status' => MinecraftServerStatus::DeleteFailed,
                'last_error' => $exception->getMessage(),
            ]);
        });
    }
}
