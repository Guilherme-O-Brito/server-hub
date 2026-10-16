<?php

namespace Tests\Unit\Events;

use App\Events\ExecutionSlotStatusChanged;
use App\enums\ExecutionSlotStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionSlotStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcasts_on_the_execution_slots_channel_with_the_expected_event_name(): void
    {
        $event = new ExecutionSlotStatusChanged(ExecutionSlot::factory()->create());

        $this->assertSame('slot.status.changed', $event->broadcastAs());
        $this->assertSame(['private-execution-slots'], array_map(
            fn (PrivateChannel $channel) => $channel->name,
            $event->broadcastOn()
        ));
    }

    public function test_broadcast_payload_includes_status_and_server_details_for_an_allocated_slot(): void
    {
        $owner = User::factory()->create(['name' => 'Alice']);
        $server = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Creative World',
        ]);
        $slot = ExecutionSlot::factory()->occupied($server)->create([
            'status' => ExecutionSlotStatus::Allocated,
        ]);

        $payload = (new ExecutionSlotStatusChanged($slot))->broadcastWith();

        $this->assertSame([
            'id' => $slot->id,
            'status' => ExecutionSlotStatus::Allocated,
            'server_type' => MinecraftServer::MORPH_TYPE,
            'server' => [
                'server_name' => 'Creative World',
                'owner' => [
                    'name' => 'Alice',
                ],
            ],
        ], $payload);
    }

    public function test_broadcast_payload_uses_null_server_details_for_a_free_slot(): void
    {
        $slot = ExecutionSlot::factory()->create([
            'status' => ExecutionSlotStatus::Free,
        ]);

        $payload = (new ExecutionSlotStatusChanged($slot))->broadcastWith();

        $this->assertSame([
            'id' => $slot->id,
            'status' => ExecutionSlotStatus::Free,
            'server_type' => null,
            'server' => null,
        ], $payload);
    }
}
