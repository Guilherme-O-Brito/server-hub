<?php

namespace Tests\Unit\Actions\ExecutionSlot;

use App\Actions\ExecutionSlot\AllocateExecutionSlotAction;
use App\Exceptions\NoExecutionSlotAvailableException;
use App\MinecraftServerStatus;
use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AllocateExecutionSlotActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_allocates_first_free_slot_ordered_by_slot_number_and_returns_it(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $secondSlot = ExecutionSlot::factory()->create([
            'slot_number' => 2,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);
        $firstSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $allocatedSlot = DB::transaction(fn () => (new AllocateExecutionSlotAction())->execute($minecraftServer));

        $firstSlot->refresh();
        $secondSlot->refresh();

        $this->assertTrue($allocatedSlot->is($firstSlot));
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $firstSlot->status);
        $this->assertTrue($firstSlot->server->is($minecraftServer));
        $this->assertSame($minecraftServer->id, $firstSlot->server_id);
        $this->assertSame($minecraftServer->getMorphClass(), $firstSlot->server_type);
        $this->assertSame(ExecutionSlot::STATUS_FREE, $secondSlot->status);
        $this->assertNull($secondSlot->server_id);
        $this->assertNull($secondSlot->server_type);
    }

    public function test_execute_ignores_non_free_slots_and_does_not_alter_them(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $allocatedServer = $this->createMinecraftServer($otherOwner);

        $allocatedSlot = ExecutionSlot::factory()->occupied($allocatedServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);
        $deletingSlot = ExecutionSlot::factory()->create([
            'slot_number' => 2,
            'status' => ExecutionSlot::STATUS_DELETING,
        ]);
        $provisioningSlot = ExecutionSlot::factory()->create([
            'slot_number' => 3,
            'status' => ExecutionSlot::STATUS_PROVISIONING,
        ]);
        $failedSlot = ExecutionSlot::factory()->create([
            'slot_number' => 4,
            'status' => ExecutionSlot::STATUS_FAILED,
        ]);
        $freeSlot = ExecutionSlot::factory()->create([
            'slot_number' => 5,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        $allocated = DB::transaction(fn () => (new AllocateExecutionSlotAction())->execute($minecraftServer));

        $allocatedSlot->refresh();
        $deletingSlot->refresh();
        $provisioningSlot->refresh();
        $failedSlot->refresh();
        $freeSlot->refresh();

        $this->assertTrue($allocated->is($freeSlot));
        $this->assertTrue($allocatedSlot->server->is($allocatedServer));
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $allocatedSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_DELETING, $deletingSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_PROVISIONING, $provisioningSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_FAILED, $failedSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $freeSlot->status);
        $this->assertTrue($freeSlot->server->is($minecraftServer));
        $this->assertNull($deletingSlot->server_id);
        $this->assertNull($provisioningSlot->server_id);
        $this->assertNull($failedSlot->server_id);
    }

    public function test_execute_throws_when_no_free_slot_is_available(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);
        $allocatedServer = $this->createMinecraftServer($otherOwner);

        $allocatedSlot = ExecutionSlot::factory()->occupied($allocatedServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);
        $deletingSlot = ExecutionSlot::factory()->create([
            'slot_number' => 2,
            'status' => ExecutionSlot::STATUS_DELETING,
        ]);
        $provisioningSlot = ExecutionSlot::factory()->create([
            'slot_number' => 3,
            'status' => ExecutionSlot::STATUS_PROVISIONING,
        ]);
        $failedSlot = ExecutionSlot::factory()->create([
            'slot_number' => 4,
            'status' => ExecutionSlot::STATUS_FAILED,
        ]);

        try {
            DB::transaction(fn () => (new AllocateExecutionSlotAction())->execute($minecraftServer));
            $this->fail('Expected NoExecutionSlotAvailableException to be thrown.');
        } catch (NoExecutionSlotAvailableException $exception) {
            $this->assertSame('No execution slot available.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $allocatedSlot->refresh();
        $deletingSlot->refresh();
        $provisioningSlot->refresh();
        $failedSlot->refresh();

        $this->assertTrue($allocatedSlot->server->is($allocatedServer));
        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $allocatedSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_DELETING, $deletingSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_PROVISIONING, $provisioningSlot->status);
        $this->assertSame(ExecutionSlot::STATUS_FAILED, $failedSlot->status);
        $this->assertNull($deletingSlot->server_id);
        $this->assertNull($provisioningSlot->server_id);
        $this->assertNull($failedSlot->server_id);
    }

    public function test_execute_does_not_associate_same_server_to_multiple_slots_when_database_constraint_exists(): void
    {
        $owner = User::factory()->create();
        $minecraftServer = $this->createMinecraftServer($owner);

        $existingSlot = ExecutionSlot::factory()->occupied($minecraftServer)->create([
            'slot_number' => 1,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);
        $freeSlot = ExecutionSlot::factory()->create([
            'slot_number' => 2,
            'status' => ExecutionSlot::STATUS_FREE,
        ]);

        try {
            DB::transaction(fn () => (new AllocateExecutionSlotAction())->execute($minecraftServer));
            $this->fail('Expected QueryException to be thrown.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $existingSlot->refresh();
        $freeSlot->refresh();

        $this->assertSame(ExecutionSlot::STATUS_ALLOCATED, $existingSlot->status);
        $this->assertTrue($existingSlot->server->is($minecraftServer));
        $this->assertSame(ExecutionSlot::STATUS_FREE, $freeSlot->status);
        $this->assertNull($freeSlot->server_id);
        $this->assertNull($freeSlot->server_type);
    }

    private function createMinecraftServer(User $owner): MinecraftServer
    {
        return $owner->ownedMinecraftServers()->create([
            'server_name' => 'Allocated Server',
            'motd' => 'Allocated motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
            'status' => MinecraftServerStatus::Stopped,
        ]);
    }
}
