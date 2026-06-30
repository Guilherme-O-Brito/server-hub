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

    public function test_authenticated_user_can_list_enabled_versions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $enabledVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $enabledVersion->id,
            'version' => '1.20.1',
            'is_enabled' => true,
        ]);
    }

    public function test_authenticated_user_does_not_receive_disabled_versions_on_index(): void
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
        ]);
        $response->assertJsonMissing([
            'id' => $disabledVersion->id,
            'version' => '1.19.4',
        ]);
    }

    public function test_guest_cannot_list_versions(): void
    {
        $response = $this->get(self::ROUTE);

        $response->assertRedirect('/login');
    }
}
