<?php

namespace Tests\Unit\Actions\MinecraftOperator;

use App\Actions\MinecraftOperator\CreateMinecraftOperatorAction;
use App\Exceptions\MinecraftServerStateException;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateMinecraftOperatorActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_operator_entry_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner, MinecraftServerStatus::Stopped);

        $result = (new CreateMinecraftOperatorAction())->execute($minecraftServer, [
            'nickname' => 'Steve_01',
        ]);

        $this->assertNull($result);
        $this->assertDatabaseHas('minecraft_operators', [
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
            (new CreateMinecraftOperatorAction())->execute($minecraftServer, [
                'nickname' => 'BlockedNick',
            ]);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is not stopped.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();

        $this->assertSame($status, $minecraftServer->status);
        $this->assertDatabaseMissing('minecraft_operators', [
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
        return MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Operator Server',
            'motd' => 'Operator motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ]);
    }
}
