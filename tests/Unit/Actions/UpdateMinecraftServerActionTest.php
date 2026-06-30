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

        Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id;
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
