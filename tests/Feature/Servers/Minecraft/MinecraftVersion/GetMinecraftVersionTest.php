<?php

namespace Tests\Feature\Servers\Minecraft\MinecraftVersion;

use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMinecraftVersionTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/servers/minecraft/version';

    public function test_authenticated_user_can_list_only_enabled_versions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $enabledVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();
        $disabledVersion = MinecraftVersion::factory()->disabled()->version('1.19.4')->create();

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $enabledVersion->id,
            'version' => '1.20.1',
            'is_enabled' => true,
        ]);
        $response->assertJsonMissing([
            'id' => $disabledVersion->id,
            'version' => '1.19.4',
        ]);
    }

    public function test_authenticated_user_receives_enabled_versions_ordered_by_minecraft_version(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $oldVersion = MinecraftVersion::factory()->enabled()->version('1.8.9')->create();
        $newVersion = MinecraftVersion::factory()->enabled()->version('1.21.2')->create();
        $middleVersion = MinecraftVersion::factory()->enabled()->version('1.20.6')->create();

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertJsonPath('0.id', $newVersion->id);
        $response->assertJsonPath('0.version', '1.21.2');
        $response->assertJsonPath('1.id', $middleVersion->id);
        $response->assertJsonPath('1.version', '1.20.6');
        $response->assertJsonPath('2.id', $oldVersion->id);
        $response->assertJsonPath('2.version', '1.8.9');
    }

    public function test_guest_cannot_list_versions(): void
    {
        $response = $this->get(self::ROUTE);

        $response->assertRedirect('/login');
    }
}
