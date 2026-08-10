<?php

namespace Tests\Feature\Servers\Minecraft\MinecraftVersion;

use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIndexMinecraftVersionTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/admin/servers/minecraft/version';

    public function test_guest_cannot_access_admin_minecraft_version_index(): void
    {
        MinecraftVersion::factory()->enabled()->create();

        $response = $this->get(self::ROUTE);

        $response->assertRedirect('/login');
    }

    public function test_authenticated_non_admin_cannot_access_admin_minecraft_version_index(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        MinecraftVersion::factory()->enabled()->create();

        $response = $this->actingAs($user)->getJson(self::ROUTE);

        $response->assertForbidden();
    }

    public function test_admin_receives_empty_list_when_there_are_no_minecraft_versions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->getJson(self::ROUTE);

        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function test_admin_index_returns_enabled_and_disabled_versions_with_expected_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $enabledVersion = MinecraftVersion::factory()
            ->enabled()
            ->version('1.20.1')
            ->create();
        $disabledVersion = MinecraftVersion::factory()
            ->disabled()
            ->version('1.21.2')
            ->create();

        $response = $this->actingAs($admin)->getJson(self::ROUTE);

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'version',
                'sort_order',
                'is_enabled',
                'created_at',
                'updated_at',
            ],
        ]);

        $returnedVersions = $response->json();

        $this->assertEqualsCanonicalizing(
            ['id', 'version', 'sort_order', 'is_enabled', 'created_at', 'updated_at'],
            array_keys($returnedVersions[0])
        );
        $this->assertSame($disabledVersion->id, $returnedVersions[0]['id']);
        $this->assertSame('1.21.2', $returnedVersions[0]['version']);
        $this->assertSame($disabledVersion->sort_order, $returnedVersions[0]['sort_order']);
        $this->assertFalse($returnedVersions[0]['is_enabled']);
        $this->assertSame($enabledVersion->id, $returnedVersions[1]['id']);
        $this->assertSame('1.20.1', $returnedVersions[1]['version']);
        $this->assertSame($enabledVersion->sort_order, $returnedVersions[1]['sort_order']);
        $this->assertTrue($returnedVersions[1]['is_enabled']);
    }

    public function test_admin_index_orders_versions_by_minecraft_version_descending(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $oldVersion = MinecraftVersion::factory()->enabled()->version('1.8.9')->create();
        $newVersion = MinecraftVersion::factory()->disabled()->version('1.21.2')->create();
        $middleVersion = MinecraftVersion::factory()->enabled()->version('1.20.6')->create();

        $response = $this->actingAs($admin)->getJson(self::ROUTE);

        $response->assertOk();
        $this->assertSame(
            [$newVersion->id, $middleVersion->id, $oldVersion->id],
            array_column($response->json(), 'id')
        );
    }
}
