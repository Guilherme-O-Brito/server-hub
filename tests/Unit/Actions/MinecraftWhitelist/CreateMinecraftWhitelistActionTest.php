<?php

namespace Tests\Unit\Actions\MinecraftWhitelist;

use App\Actions\MinecraftWhitelist\CreateMinecraftWhitelistAction;
use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateMinecraftWhitelistActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_whitelist_entry_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped);

        $result = (new CreateMinecraftWhitelistAction())->execute($minecraftServer, [
            'nickname' => 'Steve_01',
        ]);

        $this->assertNull($result);
        $this->assertDatabaseHas('minecraft_whitelists', [
            'minecraft_server_id' => $minecraftServer->id,
            'nickname' => 'Steve_01',
        ]);

        Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_execute_rejects_non_stopped_servers_without_creating_entry_or_dispatching_job(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, $status);

        try {
            (new CreateMinecraftWhitelistAction())->execute($minecraftServer, [
                'nickname' => 'BlockedNick',
            ]);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is not stopped.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();

        $this->assertSame($status, $minecraftServer->status);
        $this->assertDatabaseMissing('minecraft_whitelists', [
            'minecraft_server_id' => $minecraftServer->id,
            'nickname' => 'BlockedNick',
        ]);
        Queue::assertNothingPushed();
    }

    public static function invalidServerStatuses(): array
    {
        return [
            'running' => [MinecraftServerStatus::Running],
            'starting' => [MinecraftServerStatus::Starting],
            'stopping' => [MinecraftServerStatus::Stopping],
            'failed' => [MinecraftServerStatus::Failed],
            'deleting' => [MinecraftServerStatus::Deleting],
            'provisioning' => [MinecraftServerStatus::Provisioning],
            'restarting' => [MinecraftServerStatus::Restarting],
            'delete failed' => [MinecraftServerStatus::DeleteFailed],
            'null status' => [null],
        ];
    }

    private function createMinecraftServer(User $owner, ?MinecraftServerStatus $status): MinecraftServer
    {
        return $owner->ownedMinecraftServers()->create([
            'server_name' => 'Whitelist Server',
            'motd' => 'Whitelist motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ]);
    }
}
