<?php

namespace Tests\Unit\Actions;

use App\Actions\ExecutionSlot\AllocateExecutionSlotAction;
use App\Actions\StartMinecraftServerAction;
use App\Exceptions\MinecraftServerStateException;
use App\Exceptions\NoExecutionSlotAvailableException;
use App\Jobs\StartMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StartMinecraftServerActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_allocates_slot_marks_server_starting_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped, [
            'last_error' => 'previous error',
            'operation_id' => $this->operationId(3),
        ]);
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $result = (new StartMinecraftServerAction(new AllocateExecutionSlotAction()))->execute($minecraftServer);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertNull($result);
        $this->assertSame(MinecraftServerStatus::Starting, $minecraftServer->status);
        $this->assertValidOperationId($minecraftServer->operation_id);
        $this->assertNotSame($this->operationId(3), $minecraftServer->operation_id);
        $this->assertNull($minecraftServer->last_error);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));

        Queue::assertPushed(StartMinecraftServerJob::class, function (StartMinecraftServerJob $job) use ($minecraftServer, $executionSlot) {
            return $job->serverId === $minecraftServer->id
                && $job->slotId === $executionSlot->id
                && $job->operationId === $minecraftServer->operation_id;
        });
    }

    public function test_execute_calls_allocate_execution_slot_action(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped, [
            'operation_id' => $this->operationId(7),
        ]);
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $allocateAction = $this->createMock(AllocateExecutionSlotAction::class);
        $allocateAction->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (MinecraftServer $passedServer) use ($minecraftServer) {
                return $passedServer->is($minecraftServer)
                    && $passedServer->status === MinecraftServerStatus::Stopped;
            }))
            ->willReturn($executionSlot);

        (new StartMinecraftServerAction($allocateAction))->execute($minecraftServer);

        $this->assertSame(MinecraftServerStatus::Starting, $minecraftServer->refresh()->status);
        $this->assertValidOperationId($minecraftServer->operation_id);
        $this->assertNotSame($this->operationId(7), $minecraftServer->operation_id);
        Queue::assertPushed(StartMinecraftServerJob::class, function (StartMinecraftServerJob $job) use ($minecraftServer, $executionSlot) {
            return $job->serverId === $minecraftServer->id
                && $job->slotId === $executionSlot->id
                && $job->operationId === $minecraftServer->operation_id;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_execute_rejects_non_stopped_servers_without_dispatching_job(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, $status, [
            'operation_id' => $this->operationId(5),
        ]);
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        try {
            (new StartMinecraftServerAction(new AllocateExecutionSlotAction()))->execute($minecraftServer);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is not stopped.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame($status, $minecraftServer->status);
        $this->assertSame($this->operationId(5), $minecraftServer->operation_id);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
        Queue::assertNothingPushed();
    }

    public function test_execute_rolls_back_when_no_execution_slot_is_available_without_dispatching_job(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped, [
            'operation_id' => $this->operationId(2),
        ]);

        try {
            (new StartMinecraftServerAction(new AllocateExecutionSlotAction()))->execute($minecraftServer);
            $this->fail('Expected NoExecutionSlotAvailableException to be thrown.');
        } catch (NoExecutionSlotAvailableException $exception) {
            $this->assertSame('No execution slot available.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->status);
        $this->assertSame($this->operationId(2), $minecraftServer->operation_id);
        $this->assertDatabaseMissing('execution_slots', [
            'server_id' => $minecraftServer->id,
            'server_type' => $minecraftServer->getMorphClass(),
        ]);
        Queue::assertNothingPushed();
    }

    public function test_execute_rolls_back_partial_state_when_allocate_action_fails_before_commit(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped, [
            'operation_id' => $this->operationId(2),
        ]);

        $allocateAction = $this->createMock(AllocateExecutionSlotAction::class);
        $allocateAction->expects($this->once())
            ->method('execute')
            ->willThrowException(new NoExecutionSlotAvailableException('No execution slot available.'));

        try {
            (new StartMinecraftServerAction($allocateAction))->execute($minecraftServer);
            $this->fail('Expected NoExecutionSlotAvailableException to be thrown.');
        } catch (NoExecutionSlotAvailableException $exception) {
            $this->assertSame('No execution slot available.', $exception->getMessage());
        }

        $this->assertSame(MinecraftServerStatus::Stopped, $minecraftServer->refresh()->status);
        $this->assertSame($this->operationId(2), $minecraftServer->operation_id);
        Queue::assertNothingPushed();
    }

    public static function invalidServerStatuses(): array
    {
        return [
            'running' => [MinecraftServerStatus::Running],
            'starting' => [MinecraftServerStatus::Starting],
            'failed' => [MinecraftServerStatus::Failed],
            'deleting' => [MinecraftServerStatus::Deleting],
            'provisioning' => [MinecraftServerStatus::Provisioning],
            'stopping' => [MinecraftServerStatus::Stopping],
            'restarting' => [MinecraftServerStatus::Restarting],
            'delete failed' => [MinecraftServerStatus::DeleteFailed],
            'null status' => [null],
        ];
    }

    private function createMinecraftServer(User $owner, ?MinecraftServerStatus $status, array $attributes = []): MinecraftServer
    {
        return MinecraftServer::factory()->for($owner, 'owner')->create(array_merge([
            'server_name' => 'Action Start Server',
            'motd' => 'Action start motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ], $attributes));
    }
}
