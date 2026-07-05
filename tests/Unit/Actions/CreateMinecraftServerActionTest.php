<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateMinecraftServerAction;
use App\Jobs\CreateMinecraftInfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateMinecraftServerActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_minecraft_server_dispatches_job_and_returns_null(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'name' => 'Alice',
        ]);
        $minecraftVersion = MinecraftVersion::factory()->enabled()->create();

        $action = new CreateMinecraftServerAction();

        $result = $action->execute($user, [
            'server_name' => 'Action Server',
            'difficulty' => 2,
            'minecraft_version_id' => $minecraftVersion->id,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        $this->assertNull($result);

        $minecraftServer = MinecraftServer::query()
            ->where('owner_id', $user->id)
            ->where('server_name', 'Action Server')
            ->firstOrFail();

        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $minecraftServer->id,
            'owner_id' => $user->id,
            'server_name' => 'Action Server',
            'motd' => "{$user->name}'s minecraft server",
            'difficulty' => 2,
            'minecraft_version_id' => $minecraftVersion->id,
            'force_gamemode' => true,
            'allow_flight' => true,
        ]);

        Queue::assertPushed(CreateMinecraftInfrastructureJob::class, function (CreateMinecraftInfrastructureJob $job) use ($minecraftServer) {
            return $job->serverId === $minecraftServer->id;
        });
    }
}
