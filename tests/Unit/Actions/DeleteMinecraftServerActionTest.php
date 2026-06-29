<?php

namespace Tests\Unit\Actions;

use App\Actions\DeleteMinecraftServerAction;
use App\Exceptions\MinecraftServerStateException;
use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeleteMinecraftServerActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_marks_server_as_deleting_and_dispatches_job(): void
    {
        Queue::fake();

        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Delete Server',
            'motd' => 'Delete motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Stopped,
        ]);

        $action = new DeleteMinecraftServerAction();

        $action->execute($minecraftServer);

        $minecraftServer->refresh();

        $this->assertSame(MinecraftServerStatus::Deleting, $minecraftServer->status);
        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $minecraftServer->id,
            'status' => MinecraftServerStatus::Deleting->value,
        ]);

        Queue::assertPushed(DeleteMinecraftinfrastructureJob::class, function (DeleteMinecraftinfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id;
        });
    }

    #[DataProvider('invalidServerStatuses')]
    public function test_execute_rejects_non_stopped_servers_without_dispatching_job(?MinecraftServerStatus $status): void
    {
        Queue::fake();

        $owner = User::factory()->create();

        $minecraftServer = $owner->ownedMinecraftServers()->create([
            'server_name' => 'Delete Server',
            'motd' => 'Delete motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => $status,
        ]);

        try {
            (new DeleteMinecraftServerAction())->execute($minecraftServer);
            $this->fail('Expected MinecraftServerStateException to be thrown.');
        } catch (MinecraftServerStateException $exception) {
            $this->assertSame('Minecraft server is not stopped.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $minecraftServer->refresh();

        $this->assertSame($status, $minecraftServer->status);
        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $minecraftServer->id,
            'status' => $status?->value,
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
}
