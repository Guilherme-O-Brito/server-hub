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
use RuntimeException;
use Tests\TestCase;

class CreateMinecraftWhitelistActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_whitelist_entry_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped, [
            'last_error' => 'previous error',
            'operation_id' => $this->operationId(6),
        ]);

        $result = (new CreateMinecraftWhitelistAction())->execute($minecraftServer, [
            'nickname' => 'Steve_01',
        ]);

        $this->assertNull($result);
        $this->assertDatabaseHas('minecraft_whitelists', [
            'minecraft_server_id' => $minecraftServer->id,
            'nickname' => 'Steve_01',
        ]);

        $minecraftServer->refresh();
        $this->assertSame(MinecraftServerStatus::Provisioning, $minecraftServer->status);
        $this->assertValidOperationId($minecraftServer->operation_id);
        $this->assertNotSame($this->operationId(6), $minecraftServer->operation_id);
        $this->assertNull($minecraftServer->last_error);

        Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id
                && $job->operationId === $minecraftServer->operation_id;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_execute_rejects_non_stopped_servers_without_creating_entry_or_dispatching_job(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, $status, [
            'operation_id' => $this->operationId(7),
        ]);

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
        $this->assertSame($this->operationId(7), $minecraftServer->operation_id);
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

    public function test_execute_rolls_back_server_and_whitelist_when_transaction_fails(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped, [
            'last_error' => 'preserved error',
            'operation_id' => $this->operationId(3),
        ]);

        MinecraftServer::updated(function () {
            throw new RuntimeException('Fail inside whitelist create transaction');
        });

        try {
            (new CreateMinecraftWhitelistAction())->execute($minecraftServer, [
                'nickname' => 'BlockedNick',
            ]);
            $this->fail('Expected the whitelist create transaction to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fail inside whitelist create transaction', $exception->getMessage());
        } finally {
            MinecraftServer::flushEventListeners();
        }

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame($this->operationId(3), $minecraftServer->operation_id);
        $this->assertSame('preserved error', $minecraftServer->last_error);
        $this->assertDatabaseMissing('minecraft_whitelists', [
            'minecraft_server_id' => $minecraftServer->id,
            'nickname' => 'BlockedNick',
        ]);
        Queue::assertNothingPushed();
    }

    private function createMinecraftServer(User $owner, ?MinecraftServerStatus $status, array $attributes = []): MinecraftServer
    {
        return MinecraftServer::factory()->for($owner, 'owner')->create(array_merge([
            'server_name' => 'Whitelist Server',
            'motd' => 'Whitelist motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ], $attributes));
    }
}
