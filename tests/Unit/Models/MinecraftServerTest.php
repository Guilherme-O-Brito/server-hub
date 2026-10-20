<?php

namespace Tests\Unit\Models;

use App\Events\MinecraftServerStatusChanged;
use App\enums\MinecraftServerStatus;
use App\Models\MinecraftServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MinecraftServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_status_persists_the_status_and_dispatches_the_broadcast_event(): void
    {
        Event::fake();

        $server = MinecraftServer::factory()->create([
            'status' => MinecraftServerStatus::Stopped,
        ]);

        $server->updateStatus(MinecraftServerStatus::Running);
        $server->refresh();

        $this->assertSame(MinecraftServerStatus::Running, $server->status);
        Event::assertDispatched(MinecraftServerStatusChanged::class, function (MinecraftServerStatusChanged $event) use ($server) {
            return $event->server->is($server)
                && $event->server->status === MinecraftServerStatus::Running;
        });
        Event::assertDispatchedTimes(MinecraftServerStatusChanged::class, 1);
    }

    public function test_get_service_name_returns_the_console_service_name(): void
    {
        $server = MinecraftServer::factory()->create(['id' => 42]);

        $this->assertSame('minecraft-42-console-service', $server->getServiceName());
    }
}
