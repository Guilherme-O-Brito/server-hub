<?php

namespace Tests\Unit\Models;

use App\Events\ExecutionSlotStatusChanged;
use App\enums\MinecraftServerStatus;
use App\enums\ExecutionSlotStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ExecutionSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_allocated_to_requires_matching_server_and_allocated_status(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $otherServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        $this->assertTrue($executionSlot->isAllocatedTo($minecraftServer));
        $this->assertFalse($executionSlot->isAllocatedTo($otherServer));

        $executionSlot->update(['status' => ExecutionSlotStatus::Free]);

        $this->assertFalse($executionSlot->isAllocatedTo($minecraftServer));
    }

    public function test_release_frees_slot_allocated_to_expected_server(): void
    {
        Event::fake();

        $minecraftServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        $released = $executionSlot->release($minecraftServer);

        $executionSlot->refresh();

        $this->assertTrue($released);
        $this->assertSame(ExecutionSlotStatus::Free, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
        Event::assertDispatched(ExecutionSlotStatusChanged::class, function (ExecutionSlotStatusChanged $event) use ($executionSlot) {
            return $event->slot->is($executionSlot)
                && $event->slot->status === ExecutionSlotStatus::Free;
        });
        Event::assertDispatchedTimes(ExecutionSlotStatusChanged::class, 1);
    }

    public function test_update_status_persists_status_and_dispatches_event_with_updated_slot(): void
    {
        Event::fake();

        $executionSlot = ExecutionSlot::factory()->create();

        $executionSlot->updateStatus(ExecutionSlotStatus::Provisioning);

        $executionSlot->refresh();

        $this->assertSame(ExecutionSlotStatus::Provisioning, $executionSlot->status);
        Event::assertDispatched(ExecutionSlotStatusChanged::class, function (ExecutionSlotStatusChanged $event) use ($executionSlot) {
            return $event->slot->is($executionSlot)
                && $event->slot->status === ExecutionSlotStatus::Provisioning;
        });
        Event::assertDispatchedTimes(ExecutionSlotStatusChanged::class, 1);
    }

    public function test_release_does_not_free_slot_reallocated_to_another_server(): void
    {
        $staleServer = $this->createMinecraftServer();
        $currentServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($currentServer)->create();

        $released = $executionSlot->release($staleServer);

        $executionSlot->refresh();

        $this->assertFalse($released);
        $this->assertSame(ExecutionSlotStatus::Allocated, $executionSlot->status);
        $this->assertTrue($executionSlot->isAllocatedTo($currentServer));
    }

    private function createMinecraftServer(): MinecraftServer
    {
        $owner = User::factory()->create();

        return MinecraftServer::factory()->for($owner, 'owner')->create([
            'status' => MinecraftServerStatus::Stopped,
        ]);
    }
}
