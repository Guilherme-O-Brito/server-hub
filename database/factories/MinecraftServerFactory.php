<?php

namespace Database\Factories;

use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
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
            'motd' => fake()->sentence(),
            'difficulty' => fake()->numberBetween(0, 3),
            'minecraft_version_id' => MinecraftVersion::factory()->enabled(),
            'force_gamemode' => fake()->boolean(),
            'allow_flight' => fake()->boolean(),
        ];
    }
}
