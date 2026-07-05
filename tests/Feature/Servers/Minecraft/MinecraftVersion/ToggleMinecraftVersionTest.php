<?php

namespace Tests\Feature\Servers\Minecraft\MinecraftVersion;

use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleMinecraftVersionTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/servers/minecraft/version';

    public function test_admin_can_toggle_enabled_version_to_disabled(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $minecraftVersion = MinecraftVersion::factory()->enabled()->create();

        $response = $this->actingAs($admin)->post(self::ROUTE."/{$minecraftVersion->id}/toggle");

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft version toggled.']);
        $this->assertFalse($minecraftVersion->refresh()->is_enabled);
    }

    public function test_admin_can_toggle_disabled_version_to_enabled(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $minecraftVersion = MinecraftVersion::factory()->disabled()->create();

        $response = $this->actingAs($admin)->post(self::ROUTE."/{$minecraftVersion->id}/toggle");

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft version toggled.']);
        $this->assertTrue($minecraftVersion->refresh()->is_enabled);
    }

    public function test_common_user_cannot_toggle_minecraft_version(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $minecraftVersion = MinecraftVersion::factory()->enabled()->create();

        $response = $this->actingAs($user)->post(self::ROUTE."/{$minecraftVersion->id}/toggle");

        $response->assertForbidden();
        $this->assertTrue($minecraftVersion->refresh()->is_enabled);
    }
}
