<?php

namespace Database\Factories;

use App\Models\MinecraftOperator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MinecraftOperator>
 */
class MinecraftOperatorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nickname' => mb_substr(fake()->name(), 0, 16)
        ];
    }
}
