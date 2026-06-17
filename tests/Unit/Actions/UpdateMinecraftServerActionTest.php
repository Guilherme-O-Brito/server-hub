<?php

namespace Tests\Unit\Actions;

use App\Actions\UpdateMinecraftServerAction;
use App\Jobs\UpdateMinecraftInfrastructureJob;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

        $minecraftServer = $user->ownedMinecraftServers()->create([
            'server_name' => 'Old Server',
            'motd' => 'Old motd',
            'difficulty' => 0,
            'force_gamemode' => true,
            'allow_flight' => false,
        ]);

        $action = new UpdateMinecraftServerAction();

        $action->execute($user, $minecraftServer, [
            'server_name' => 'Updated Server',
            'difficulty' => 2,
        ]);

        $minecraftServer->refresh();

        $this->assertSame('Updated Server', $minecraftServer->server_name);
        $this->assertSame("{$user->name}'s minecraft server", $minecraftServer->motd);
        $this->assertSame(2, $minecraftServer->difficulty);
        $this->assertTrue($minecraftServer->force_gamemode);
        $this->assertTrue($minecraftServer->allow_flight);

        Queue::assertPushed(UpdateMinecraftInfrastructureJob::class, function (UpdateMinecraftInfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id;
        });
    }
}
