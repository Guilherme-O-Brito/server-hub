<?php

namespace Database\Factories;

use App\Models\MinecraftVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MinecraftVersion>
 */
class MinecraftVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => fake()->unique()->numerify('##.#'),
            'is_enabled' => true
        ];

    }

    public function enabled(): static
    {
        return $this->state(fn () => [
            'is_enabled' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => [
            'is_enabled' => false,
        ]);
    }

    public function version(string $version): static
    {
        return $this->state(fn () => [
            'version' => $version,
        ]);
    }

}
