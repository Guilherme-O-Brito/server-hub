<?php

namespace Tests\Unit\Actions;

use App\Actions\UpdateMinecraftServerAction;
use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class UpdateMinecraftServerActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_updates_minecraft_server_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'name' => 'Alice',
        ]);
        $currentVersion = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $newVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();

        $minecraftServer = $user->ownedMinecraftServers()->create([
            'server_name' => 'Old Server',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'minecraft_version_id' => $currentVersion->id,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Stopped,
            'last_error' => 'previous error',
            'operation_id' => $this->operationId(12),
        ]);

        $action = new UpdateMinecraftServerAction();

        $action->execute($user, $minecraftServer, [
            'server_name' => 'Updated Server',
            'difficulty' => 2,
            'minecraft_version_id' => $newVersion->id,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $minecraftServer->refresh();

        $this->assertSame('Updated Server', $minecraftServer->server_name);
        $this->assertSame("{$user->name}'s minecraft server", $minecraftServer->motd);
        $this->assertSame(2, $minecraftServer->difficulty);
        $this->assertSame($newVersion->id, $minecraftServer->minecraft_version_id);
        $this->assertTrue($minecraftServer->force_gamemode);
        $this->assertTrue($minecraftServer->allow_flight);
        $this->assertSame(MinecraftServerStatus::Provisioning, $minecraftServer->status);
        $this->assertValidOperationId($minecraftServer->operation_id);
        $this->assertNotSame($this->operationId(12), $minecraftServer->operation_id);
        $this->assertNull($minecraftServer->last_error);

        Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id
                && $job->operationId === $minecraftServer->operation_id;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_execute_rejects_non_stopped_servers_without_updating_or_dispatching_job(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'name' => 'Alice',
        ]);
        $currentVersion = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $newVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();

        $minecraftServer = $user->ownedMinecraftServers()->create([
            'server_name' => 'Old Server',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'minecraft_version_id' => $currentVersion->id,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
            'operation_id' => $this->operationId(8),
        ]);

        try {
            (new UpdateMinecraftServerAction())->execute($user, $minecraftServer, [
                'server_name' => 'Updated Server',
                'difficulty' => 2,
                'minecraft_version_id' => $newVersion->id,
                'force_gamemode' => false,
                'allow_flight' => true,
            ]);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is not stopped.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();

        $this->assertSame('Old Server', $minecraftServer->server_name);
        $this->assertSame('Old motd', $minecraftServer->motd);
        $this->assertSame(0, $minecraftServer->difficulty);
        $this->assertSame($currentVersion->id, $minecraftServer->minecraft_version_id);
        $this->assertTrue($minecraftServer->force_gamemode);
        $this->assertFalse($minecraftServer->allow_flight);
        $this->assertSame($status, $minecraftServer->status);
        $this->assertSame($this->operationId(8), $minecraftServer->operation_id);
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

    public function test_execute_rolls_back_server_changes_operation_id_and_dispatch_when_transaction_fails(): void
    {
        Queue::fake();

        $user = User::factory()->create(['name' => 'Alice']);
        $currentVersion = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $newVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();
        $minecraftServer = $user->ownedMinecraftServers()->create([
            'server_name' => 'Old Server',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'minecraft_version_id' => $currentVersion->id,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Stopped,
            'last_error' => 'preserved error',
            'operation_id' => $this->operationId(3),
        ]);

        MinecraftServer::updated(function () {
            throw new RuntimeException('Fail inside update transaction');
        });

        try {
            (new UpdateMinecraftServerAction())->execute($user, $minecraftServer, [
                'server_name' => 'Updated Server',
                'motd' => 'Updated motd',
                'difficulty' => 2,
                'minecraft_version_id' => $newVersion->id,
                'force_gamemode' => false,
                'allow_flight' => true,
            ]);
            $this->fail('Expected the update transaction to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fail inside update transaction', $exception->getMessage());
        } finally {
            MinecraftServer::flushEventListeners();
        }

        $minecraftServer->refresh();

        $this->assertSame('Old Server', $minecraftServer->server_name);
        $this->assertSame($currentVersion->id, $minecraftServer->minecraft_version_id);
        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame($this->operationId(3), $minecraftServer->operation_id);
        $this->assertSame('preserved error', $minecraftServer->last_error);
        Queue::assertNothingPushed();
    }
}
