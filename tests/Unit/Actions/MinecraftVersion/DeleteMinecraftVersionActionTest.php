<?php

namespace Tests\Unit\Actions\MinecraftVersion;

use App\Actions\MinecraftVersion\DeleteMinecraftVersionAction;
use App\Exceptions\MinecraftVersionDeleteException;
use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteMinecraftVersionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_moves_servers_to_latest_enabled_replacement_and_deletes_old_version(): void
    {
        $owner = User::factory()->create();
        $versionToDelete = MinecraftVersion::factory()->enabled()->version('1.19.4')->create([
            'created_at' => now()->subDays(3),
        ]);
        $olderEnabledReplacement = MinecraftVersion::factory()->enabled()->version('1.18.2')->create([
            'created_at' => now()->subDays(2),
        ]);
        $latestDisabledVersion = MinecraftVersion::factory()->disabled()->version('1.21.1')->create([
            'created_at' => now(),
        ]);
        $latestEnabledReplacement = MinecraftVersion::factory()->enabled()->version('1.20.1')->create([
            'created_at' => now()->subDay(),
        ]);
        $firstServerUsingDeletedVersion = MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(['minecraft_version_id' => $versionToDelete->id]);
        $secondServerUsingDeletedVersion = MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(['minecraft_version_id' => $versionToDelete->id]);
        $serverUsingOtherVersion = MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(['minecraft_version_id' => $olderEnabledReplacement->id]);

        $result = (new DeleteMinecraftVersionAction())->execute($versionToDelete);

        $this->assertNull($result);
        $this->assertDatabaseMissing('minecraft_versions', [
            'id' => $versionToDelete->id,
        ]);
        $this->assertDatabaseHas('minecraft_versions', [
            'id' => $latestDisabledVersion->id,
            'is_enabled' => false,
        ]);
        $this->assertSame($latestEnabledReplacement->id, $firstServerUsingDeletedVersion->refresh()->minecraft_version_id);
        $this->assertSame($latestEnabledReplacement->id, $secondServerUsingDeletedVersion->refresh()->minecraft_version_id);
        $this->assertSame($olderEnabledReplacement->id, $serverUsingOtherVersion->refresh()->minecraft_version_id);
    }

    public function test_execute_uses_highest_id_when_enabled_replacements_have_same_created_at(): void
    {
        $owner = User::factory()->create();
        $createdAt = now();
        $versionToDelete = MinecraftVersion::factory()->enabled()->version('1.19.4')->create([
            'created_at' => $createdAt->copy()->subDay(),
        ]);
        $firstReplacement = MinecraftVersion::factory()->enabled()->version('1.20.1')->create([
            'created_at' => $createdAt,
        ]);
        $secondReplacement = MinecraftVersion::factory()->enabled()->version('1.21.1')->create([
            'created_at' => $createdAt,
        ]);
        $minecraftServer = MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(['minecraft_version_id' => $versionToDelete->id]);

        (new DeleteMinecraftVersionAction())->execute($versionToDelete);

        $this->assertGreaterThan($firstReplacement->id, $secondReplacement->id);
        $this->assertSame($secondReplacement->id, $minecraftServer->refresh()->minecraft_version_id);
    }

    public function test_execute_throws_when_no_other_enabled_version_exists_and_keeps_database_unchanged(): void
    {
        $owner = User::factory()->create();
        $versionToDelete = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();
        $disabledVersion = MinecraftVersion::factory()->disabled()->version('1.19.4')->create();
        $minecraftServer = MinecraftServer::factory()
            ->for($owner, 'owner')
            ->create(['minecraft_version_id' => $versionToDelete->id]);

        try {
            (new DeleteMinecraftVersionAction())->execute($versionToDelete);
            $this->fail('Expected MinecraftVersionDeleteException to be thrown.');
        } catch (MinecraftVersionDeleteException $exception) {
            $this->assertSame('There is no other version enabled to replace this version.', $exception->getMessage());
            $this->assertSame(409, $exception->statusCode());
        }

        $this->assertDatabaseHas('minecraft_versions', [
            'id' => $versionToDelete->id,
            'is_enabled' => true,
        ]);
        $this->assertDatabaseHas('minecraft_versions', [
            'id' => $disabledVersion->id,
            'is_enabled' => false,
        ]);
        $this->assertSame($versionToDelete->id, $minecraftServer->refresh()->minecraft_version_id);
    }
}
