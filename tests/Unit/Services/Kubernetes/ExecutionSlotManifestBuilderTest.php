<?php

namespace Tests\Unit\Services\Kubernetes;

use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\User;
use App\Services\Kubernetes\ExecutionSlotManifestBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionSlotManifestBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_extracts_expected_data_from_occupied_execution_slot(): void
    {
        $owner = User::factory()->create();

        $minecraftServer = MinecraftServer::factory()->for($owner, 'owner')->create([
            'server_name' => 'Builder Server',
            'motd' => 'Builder motd',
            'difficulty' => 2,
            'force_gamemode' => false,
            'allow_flight' => true,
        ]);

        $executionSlot = ExecutionSlot::factory()
            ->occupied($minecraftServer)
            ->create([
                'slot_number' => 1,
                'external_port' => 30000,
                'service_name' => 'server-service-1',
            ]);

        $builder = new ExecutionSlotManifestBuilder();

        $service = $builder->service($executionSlot);

        $this->assertSame('v1', $service['apiVersion']);
        $this->assertSame('Service', $service['kind']);
        $this->assertSame('server-service-1', $service['metadata']['name']);
        $this->assertSame('games', $service['metadata']['namespace']);
        $this->assertSame('NodePort', $service['spec']['type']);
        $this->assertSame("minecraft-{$minecraftServer->id}", $service['spec']['selector']['app']);
        $this->assertSame('TCP', $service['spec']['ports'][0]['protocol']);
        $this->assertSame(25565, $service['spec']['ports'][0]['port']);
        $this->assertSame(25565, $service['spec']['ports'][0]['targetPort']);
        $this->assertSame(30000, $service['spec']['ports'][0]['nodePort']);
    }

    public function test_builder_uses_no_allocated_selector_for_free_execution_slot(): void
    {
        $executionSlot = ExecutionSlot::factory()->create([
            'slot_number' => 1,
            'external_port' => 30000,
            'service_name' => 'server-service-1',
        ]);

        $builder = new ExecutionSlotManifestBuilder();

        $service = $builder->service($executionSlot);

        $this->assertSame('no-allocated', $service['spec']['selector']['app']);
    }
}
