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
            'version' => fake()->unique()->randomElement([
                '1.8.9',
                '1.12.2',
                '1.16.5',
                '1.18.2',
                '1.19.4',
                '1.20.1',
                '1.20.4',
                '1.21.1',
                '1.21.4',
            ]),
            'is_enabled' => fake()->boolean()
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
