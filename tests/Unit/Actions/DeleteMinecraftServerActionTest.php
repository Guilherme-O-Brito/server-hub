<?php

namespace Tests\Unit\Actions;

use App\Actions\DeleteMinecraftServerAction;
use App\Jobs\DeleteMinecraftinfrastructureJob;
use App\MinecraftServerStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
}
