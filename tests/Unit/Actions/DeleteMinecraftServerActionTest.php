<?php

namespace Tests\Unit\Actions;

use App\Actions\DeleteMinecraftServerAction;
use App\Exceptions\MinecraftServerStateException;
use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class DeleteMinecraftServerActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_marks_server_as_deleting_and_dispatches_job(): void
    {
        Queue::fake();

        $owner = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Delete Server',
            'motd' => 'Delete motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Stopped,
            'last_error' => 'previous error',
            'operation_id' => $this->operationId(4),
        ]);

        $action = new DeleteMinecraftServerAction();

        $action->execute($minecraftServer);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->status);
        $this->assertValidOperationId($minecraftServer->operation_id);
        $this->assertNotSame($this->operationId(4), $minecraftServer->operation_id);
        $this->assertNull($minecraftServer->last_error);
        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $minecraftServer->id,
            'status' => MinecraftServerStatus::Deleting->value,
            'operation_id' => $minecraftServer->operation_id,
        ]);

        Queue::assertPushed(DeleteMinecraftinfrastructureJob::class, function (DeleteMinecraftinfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id
                && $job->operationId === $minecraftServer->operation_id;
        });
    }

    #[DataProvider('nonStoppedServerStatuses')]
    public function test_execute_accepts_non_stopped_servers_and_dispatches_job(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Delete Server',
            'motd' => 'Delete motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
            'operation_id' => $this->operationId(9),
        ]);

        (new DeleteMinecraftServerAction())->execute($minecraftServer);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->status);
        $this->assertValidOperationId($minecraftServer->operation_id);
        $this->assertNotSame($this->operationId(9), $minecraftServer->operation_id);
        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $minecraftServer->id,
            'status' => MinecraftServerStatus::Deleting->value,
            'operation_id' => $minecraftServer->operation_id,
        ]);
        Queue::assertPushed(DeleteMinecraftinfrastructureJob::class, function (DeleteMinecraftinfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id
                && $job->operationId === $minecraftServer->operation_id;
        });
    }

    public function test_execute_rejects_server_already_being_deleted_without_changing_operation_id_or_dispatching_job(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'status' => MinecraftServerStatus::Deleting,
            'last_error' => 'delete in progress',
            'operation_id' => $this->operationId(6),
        ]);

        try {
            (new DeleteMinecraftServerAction())->execute($minecraftServer);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is already being deleted.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->status);
        $this->assertSame($this->operationId(6), $minecraftServer->operation_id);
        $this->assertSame('delete in progress', $minecraftServer->last_error);
        Queue::assertNothingPushed();
    }

    public function test_execute_rolls_back_operation_id_status_and_error_when_transaction_fails(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'status' => MinecraftServerStatus::Stopped,
            'last_error' => 'preserved error',
            'operation_id' => $this->operationId(4),
        ]);

        MinecraftServer::updated(function () {
            throw new RuntimeException('Fail inside delete transaction');
        });

        try {
            (new DeleteMinecraftServerAction())->execute($minecraftServer);
            $this->fail('Expected the delete transaction to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fail inside delete transaction', $exception->getMessage());
        } finally {
            MinecraftServer::flushEventListeners();
        }

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame($this->operationId(4), $minecraftServer->operation_id);
        $this->assertSame('preserved error', $minecraftServer->last_error);
        Queue::assertNothingPushed();
    }

    public static function nonStoppedServerStatuses(): array
    {
        return [
            'running' => [MinecraftServerStatus::Running],
            'starting' => [MinecraftServerStatus::Starting],
            'stopping' => [MinecraftServerStatus::Stopping],
            'failed' => [MinecraftServerStatus::Failed],
            'provisioning' => [MinecraftServerStatus::Provisioning],
            'restarting' => [MinecraftServerStatus::Restarting],
            'delete failed' => [MinecraftServerStatus::DeleteFailed],
            'null status' => [null],
        ];
    }
}
