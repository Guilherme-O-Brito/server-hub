<?php

namespace Tests\Unit\Models;

use App\Models\MinecraftVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinecraftVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sort_order_is_filled_from_version_when_model_is_created(): void
    {
        $minecraftVersion = MinecraftVersion::factory()->version('1.21.2')->create([
            'sort_order' => 1,
        ]);

        $this->assertSame(12102, $minecraftVersion->refresh()->sort_order);
    }

    public function test_sort_order_is_recalculated_when_version_changes(): void
    {
        $minecraftVersion = MinecraftVersion::factory()->version('1.8.9')->create();

        $minecraftVersion->update(['version' => '1.20']);

        $this->assertSame(12000, $minecraftVersion->refresh()->sort_order);
    }

    public function test_ordered_desc_scope_orders_versions_like_minecraft_releases(): void
    {
        $oldVersion = MinecraftVersion::factory()->enabled()->version('1.8.9')->create([
            'created_at' => now(),
        ]);
        $newVersion = MinecraftVersion::factory()->enabled()->version('1.21.2')->create([
            'created_at' => now()->subDay(),
        ]);
        $middleVersion = MinecraftVersion::factory()->enabled()->version('1.20.6')->create([
            'created_at' => now()->subDays(2),
        ]);

        $versions = MinecraftVersion::query()->orderedDesc()->pluck('id')->all();

        $this->assertSame([
            $newVersion->id,
            $middleVersion->id,
            $oldVersion->id,
        ], $versions);
    }
}
