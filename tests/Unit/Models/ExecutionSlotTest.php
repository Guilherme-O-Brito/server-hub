<?php

namespace Tests\Unit\Models;

use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $executionSlot->update(['status' => ExecutionSlot::STATUS_FREE]);

        $this->assertFalse($executionSlot->isAllocatedTo($minecraftServer));
    }

    public function test_release_frees_slot_allocated_to_expected_server(): void
    {
        $minecraftServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create();

        $released = $executionSlot->release($minecraftServer);

        $executionSlot->refresh();

        $this->assertTrue($released);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $executionSlot->status);
        $this->assertNull($executionSlot->server_id);
        $this->assertNull($executionSlot->server_type);
    }

    public function test_release_does_not_free_slot_reallocated_to_another_server(): void
    {
        $staleServer = $this->createMinecraftServer();
        $currentServer = $this->createMinecraftServer();
        $executionSlot = ExecutionSlot::factory()->occupied($currentServer)->create();

        $released = $executionSlot->release($staleServer);

        $executionSlot->refresh();

        $this->assertFalse($released);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $executionSlot->status);
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
