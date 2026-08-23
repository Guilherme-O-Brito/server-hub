<?php

namespace Tests\Unit\Actions\MinecraftOperator;

use App\Actions\MinecraftOperator\DeleteMinecraftOperatorAction;
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

class DeleteMinecraftOperatorActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_deletes_operator_entry_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped, [
            'last_error' => 'previous error',
            'operation_generation' => 4,
        ]);
        $minecraftOperator = $minecraftServer->operators()->create([
            'nickname' => 'Steve_01',
        ]);

        $result = (new DeleteMinecraftOperatorAction())->execute($minecraftServer, $minecraftOperator);

        $this->assertNull($result);
        $this->assertDatabaseMissing('minecraft_operators', [
            'id' => $minecraftOperator->id,
        ]);

        $minecraftServer->refresh();
        $this->assertSame(MinecraftServerStatus::Provisioning, $minecraftServer->status);
        $this->assertSame(5, $minecraftServer->operation_generation);
        $this->assertNull($minecraftServer->last_error);

        Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id
                && $job->generation === 5;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_execute_rejects_non_stopped_servers_without_deleting_entry_or_dispatching_job(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, $status, [
            'operation_generation' => 7,
        ]);
        $minecraftOperator = $minecraftServer->operators()->create([
            'nickname' => 'Steve_01',
        ]);

        try {
            (new DeleteMinecraftOperatorAction())->execute($minecraftServer, $minecraftOperator);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is not stopped.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();

        $this->assertSame($status, $minecraftServer->status);
        $this->assertSame(7, $minecraftServer->operation_generation);
        $this->assertDatabaseHas('minecraft_operators', [
            'id' => $minecraftOperator->id,
            'minecraft_server_id' => $minecraftServer->id,
            'nickname' => 'Steve_01',
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

    public function test_execute_rolls_back_server_and_keeps_operator_when_transaction_fails(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped, [
            'last_error' => 'preserved error',
            'operation_generation' => 3,
        ]);
        $minecraftOperator = $minecraftServer->operators()->create(['nickname' => 'Steve_01']);

        MinecraftServer::updated(function () {
            throw new RuntimeException('Fail inside operator delete transaction');
        });

        try {
            (new DeleteMinecraftOperatorAction())->execute($minecraftServer, $minecraftOperator);
            $this->fail('Expected the operator delete transaction to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fail inside operator delete transaction', $exception->getMessage());
        } finally {
            MinecraftServer::flushEventListeners();
        }

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame(3, $minecraftServer->operation_generation);
        $this->assertSame('preserved error', $minecraftServer->last_error);
        $this->assertDatabaseHas('minecraft_operators', ['id' => $minecraftOperator->id]);
        Queue::assertNothingPushed();
    }

    private function createMinecraftServer(User $owner, ?MinecraftServerStatus $status, array $attributes = []): MinecraftServer
    {
        return MinecraftServer::factory()->for($owner, 'owner')->create(array_merge([
            'server_name' => 'Operator Server',
            'motd' => 'Operator motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ], $attributes));
    }
}
