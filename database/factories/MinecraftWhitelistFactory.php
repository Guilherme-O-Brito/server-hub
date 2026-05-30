<?php

namespace Database\Factories;

use App\Models\MinecraftWhitelist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MinecraftWhitelist>
 */
class MinecraftWhitelistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nickname' => fake()->name()
        ];
    }
}
