<?php

namespace Database\Factories;

use App\Models\MinecraftServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MinecraftServer>
 */
class MinecraftServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_name' => fake()->word(),
            'level_name' => 'world',
            'motd' => fake()->sentence(),
            'difficulty' => fake()->numberBetween(0, 3),
            'force_gamemode' => fake()->boolean(),
            'allow_flight' => fake()->boolean(),
        ];
    }
}
