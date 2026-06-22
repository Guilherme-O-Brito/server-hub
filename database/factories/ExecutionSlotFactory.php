<?php

namespace Database\Factories;

use App\Models\ExecutionSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExecutionSlot>
 */
class ExecutionSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = ExecutionSlot::class;

    public function definition(): array
    {   
        $slot_number = $this->faker->unique()->numberBetween(1,10);
        return [
            'slot_number' => $slot_number,
            'external_port' => $this->faker->unique()->numberBetween(30000,31000),
            'service_name' => 'server-service-'.$slot_number,
            'status' => ExecutionSlot::STATUS_FREE,
            'server_id' => null,
            'server_type' => null
        ];
    }

    public function occupied($server): static
    {
        return $this->state(fn () => [
            'server_type' => $server->getMorphClass(),
            'server_id' => $server->id,
            'status' => ExecutionSlot::STATUS_ALLOCATED,
        ]);
    }
}
