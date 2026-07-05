<?php

namespace Tests\Feature\Servers\Minecraft\MinecraftVersion;

use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteMinecraftVersionTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/servers/minecraft/version';

    public function test_admin_can_delete_version_and_move_servers_to_enabled_replacement(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create(['is_admin' => false]);
        $versionToDelete = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $olderReplacementVersion = MinecraftVersion::factory()->enabled()->version('1.8.9')->create();
        $replacementVersion = MinecraftVersion::factory()->enabled()->version('1.21.2')->create();
        $serverUsingDeletedVersion = MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(['minecraft_version_id' => $versionToDelete->id]);
        $serverAlreadyUsingReplacement = MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(['minecraft_version_id' => $olderReplacementVersion->id]);

        $response = $this->actingAs($admin)->delete(self::ROUTE."/{$versionToDelete->id}");

        $response->assertOk();
        $response->assertJson(['message' => 'Minecraft version deleted.']);
        $this->assertDatabaseMissing('minecraft_versions', [
            'id' => $versionToDelete->id,
        ]);
        $this->assertSame($replacementVersion->id, $serverUsingDeletedVersion->refresh()->minecraft_version_id);
        $this->assertSame($olderReplacementVersion->id, $serverAlreadyUsingReplacement->refresh()->minecraft_version_id);
    }

    public function test_delete_fails_when_there_is_no_other_enabled_replacement_version(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create(['is_admin' => false]);
        $versionToDelete = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();
        $disabledVersion = MinecraftVersion::factory()->disabled()->version('1.19.4')->create();
        $minecraftServer = MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(['minecraft_version_id' => $versionToDelete->id]);

        $response = $this->actingAs($admin)->delete(self::ROUTE."/{$versionToDelete->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'There is no other version enabled to replace this version.',
        ]);
        $this->assertDatabaseHas('minecraft_versions', [
            'id' => $versionToDelete->id,
        ]);
        $this->assertDatabaseHas('minecraft_versions', [
            'id' => $disabledVersion->id,
            'is_enabled' => false,
        ]);
        $this->assertSame($versionToDelete->id, $minecraftServer->refresh()->minecraft_version_id);
    }

    public function test_common_user_cannot_delete_minecraft_version(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $minecraftVersion = MinecraftVersion::factory()->enabled()->create();

        $response = $this->actingAs($user)->delete(self::ROUTE."/{$minecraftVersion->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('minecraft_versions', [
            'id' => $minecraftVersion->id,
        ]);
    }
}
