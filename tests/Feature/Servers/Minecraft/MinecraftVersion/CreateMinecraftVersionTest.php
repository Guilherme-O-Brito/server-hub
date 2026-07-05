<?php

namespace Tests\Feature\Servers\Minecraft\MinecraftVersion;

use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateMinecraftVersionTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/servers/minecraft/version';

    public function test_platform_admin_can_create_minecraft_version(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'version' => '1.20.1',
            'is_enabled' => true,
        ]);

        $response->assertCreated();
        $response->assertJson(['message' => 'Minecraft version created.']);
        $this->assertDatabaseHas('minecraft_versions', [
            'version' => '1.20.1',
            'is_enabled' => true,
        ]);
    }

    public function test_common_user_cannot_create_minecraft_version(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post(self::ROUTE, [
            'version' => '1.20.1',
            'is_enabled' => true,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('minecraft_versions', [
            'version' => '1.20.1',
        ]);
    }

    public function test_create_requires_version(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'is_enabled' => true,
        ]);

        $response->assertSessionHasErrors('version');
    }

    public function test_create_requires_version_to_be_a_string(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'version' => ['1.20.1'],
            'is_enabled' => true,
        ]);

        $response->assertSessionHasErrors('version');
    }

    public function test_create_limits_version_to_eight_characters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'version' => '1234567.8',
            'is_enabled' => true,
        ]);

        $response->assertSessionHasErrors('version');
    }

    #[DataProvider('validVersions')]
    public function test_create_accepts_valid_version_formats(string $version): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'version' => $version,
            'is_enabled' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('minecraft_versions', [
            'version' => $version,
            'is_enabled' => true,
        ]);
    }

    #[DataProvider('invalidVersions')]
    public function test_create_rejects_invalid_version_formats(string $version): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'version' => $version,
            'is_enabled' => true,
        ]);

        $response->assertSessionHasErrors('version');
        $this->assertDatabaseMissing('minecraft_versions', [
            'version' => $version,
        ]);
    }

    public function test_create_rejects_duplicate_version(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        MinecraftVersion::factory()->enabled()->version('1.20.1')->create();

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'version' => '1.20.1',
            'is_enabled' => true,
        ]);

        $response->assertSessionHasErrors('version');
        $this->assertSame(1, MinecraftVersion::query()->where('version', '1.20.1')->count());
    }

    public function test_create_requires_is_enabled(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'version' => '1.20.1',
        ]);

        $response->assertSessionHasErrors('is_enabled');
    }

    public function test_create_requires_is_enabled_to_be_boolean(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(self::ROUTE, [
            'version' => '1.20.1',
            'is_enabled' => 'notabool',
        ]);

        $response->assertSessionHasErrors('is_enabled');
    }

    public static function validVersions(): array
    {
        return [
            '1.8' => ['1.8'],
            '1.8.9' => ['1.8.9'],
            '1.20.1' => ['1.20.1'],
            '26.0' => ['26.0'],
            '26.2' => ['26.2'],
        ];
    }

    public static function invalidVersions(): array
    {
        return [
            'letters' => ['abc'],
            'single number' => ['1'],
            'trailing dot' => ['1.'],
            'four segments' => ['1.2.3.4'],
            'latest' => ['latest'],
        ];
    }
}
