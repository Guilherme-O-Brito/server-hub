<?php

namespace Tests\Unit\Actions;

use App\Actions\StopMinecraftServerAction;
use App\Exceptions\MinecraftServerStateException;
use App\Jobs\StopMinecraftServerJob;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class StopMinecraftServerActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_marks_server_stopping_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        [$minecraftServer, $executionSlot] = $this->createRunningServerWithSlot($owner);

        $result = (new StopMinecraftServerAction())->execute($minecraftServer);

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertNull($result);
        $this->assertSame(MinecraftServerStatus::Stopping, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));

        Queue::assertPushed(StopMinecraftServerJob::class, function (StopMinecraftServerJob $job) use ($minecraftServer, $executionSlot) {
            return $job->serverId === $minecraftServer->id
                && $job->slotId === $executionSlot->id;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_execute_rejects_non_running_servers_without_dispatching_job(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, $status);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        try {
            (new StopMinecraftServerAction())->execute($minecraftServer);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is not running.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame($status, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));
        Queue::assertNothingPushed();
    }

    public function test_execute_requires_associated_execution_slot_without_dispatching_job(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Running);

        try {
            (new StopMinecraftServerAction())->execute($minecraftServer);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server has no execution slot.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $this->assertSame(MinecraftServerStatus::Running, $minecraftServer->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_execute_does_not_release_execution_slot_directly(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        [$minecraftServer, $executionSlot] = $this->createRunningServerWithSlot($owner);

        (new StopMinecraftServerAction())->execute($minecraftServer);

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertSame($minecraftServer->id, $executionSlot->server_id);
        $this->assertSame($minecraftServer->getMorphClass(), $executionSlot->server_type);
        Queue::assertPushed(StopMinecraftServerJob::class, 1);
    }

    public function test_execute_rolls_back_status_change_when_transaction_fails_before_commit(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        [$minecraftServer, $executionSlot] = $this->createRunningServerWithSlot($owner);

        MinecraftServer::updated(function () {
            throw new RuntimeException('Fail inside stop transaction');
        });

        try {
            (new StopMinecraftServerAction())->execute($minecraftServer);
            $this->fail('Expected the stop transaction to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fail inside stop transaction', $exception->getMessage());
        } finally {
            MinecraftServer::flushEventListeners();
        }

        $minecraftServer->refresh();
        $executionSlot->refresh();

        $this->assertSame(MinecraftServerStatus::Running, $minecraftServer->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
        $this->assertTrue($executionSlot->server->is($minecraftServer));
        Queue::assertNothingPushed();
    }

    public static function invalidServerStatuses(): array
    {
        return [
            'stopped' => [MinecraftServerStatus::Stopped],
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

    private function createRunningServerWithSlot(User $owner): array
    {
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Running);
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);

        return [$minecraftServer, $executionSlot];
    }

    private function createMinecraftServer(User $owner, ?MinecraftServerStatus $status): MinecraftServer
    {
        return $owner->ownedMinecraftServers()->create([
            'server_name' => 'Action Stop Server',
            'motd' => 'Action stop motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ]);
    }
}
