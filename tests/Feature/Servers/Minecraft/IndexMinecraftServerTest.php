<?php

namespace Tests\Feature\Servers\Minecraft;

use App\Models\ExecutionSlot;
use App\Models\MinecraftServer;
use App\Models\MinecraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexMinecraftServerTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/servers/minecraft';

    public function test_authenticated_user_can_list_owned_and_administered_minecraft_servers(): void
    {
        $user = User::factory()->create();
        $otherOwner = User::factory()->create();

        $ownedVersion = MinecraftVersion::factory()->enabled()->version('1.20.1')->create();
        $adminVersion = MinecraftVersion::factory()->enabled()->version('1.19.4')->create();
        $hiddenVersion = MinecraftVersion::factory()->enabled()->version('1.18.2')->create();

        $ownedServer = $this->createMinecraftServer($user, [
            'server_name' => 'Owned Server',
            'minecraft_version_id' => $ownedVersion->id,
        ]);
        $ownedSlot = ExecutionSlot::factory()->occupied($ownedServer)->create();

        $adminServer = $this->createMinecraftServer($otherOwner, [
            'server_name' => 'Admin Server',
            'minecraft_version_id' => $adminVersion->id,
        ]);
        $adminSlot = ExecutionSlot::factory()->occupied($adminServer)->create();
        $adminServer->admins()->attach($user->id);

        $hiddenServer = $this->createMinecraftServer($otherOwner, [
            'server_name' => 'Hidden Server',
            'minecraft_version_id' => $hiddenVersion->id,
        ]);

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $servers = $response->json('data');
        $serverIds = array_column($servers, 'id');

        $this->assertCount(2, $servers);
        $this->assertSame([$adminServer->id, $ownedServer->id], $serverIds);
        $this->assertNotContains($hiddenServer->id, $serverIds);

        $ownedServerJson = $this->serverFromResponse($servers, $ownedServer->id);
        $this->assertSame('Owned Server', $ownedServerJson['server_name']);
        $this->assertSame($ownedVersion->id, $ownedServerJson['version']['id']);
        $this->assertSame('1.20.1', $ownedServerJson['version']['version']);
        $this->assertSame($ownedSlot->id, $ownedServerJson['execution_slot']['id']);
        $this->assertSame('owner', $ownedServerJson['access_role']);

        $adminServerJson = $this->serverFromResponse($servers, $adminServer->id);
        $this->assertSame('Admin Server', $adminServerJson['server_name']);
        $this->assertSame($adminVersion->id, $adminServerJson['version']['id']);
        $this->assertSame('1.19.4', $adminServerJson['version']['version']);
        $this->assertSame($adminSlot->id, $adminServerJson['execution_slot']['id']);
        $this->assertSame('admin', $adminServerJson['access_role']);
    }

    public function test_guest_cannot_list_minecraft_servers(): void
    {
        $response = $this->get(self::ROUTE);

        $response->assertRedirect('/login');
    }

    public function test_index_returns_empty_paginated_list_when_user_has_no_visible_servers(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $hiddenServer = $this->createMinecraftServer($otherUser);

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('per_page', 8);
        $response->assertJsonPath('total', 0);
        $response->assertJsonMissing(['id' => $hiddenServer->id]);
    }

    public function test_index_does_not_duplicate_server_when_user_is_owner_and_admin(): void
    {
        $user = User::factory()->create();
        $server = $this->createMinecraftServer($user);
        $server->admins()->attach($user->id);

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();

        $serverIds = array_column($response->json('data'), 'id');

        $this->assertSame([$server->id], $serverIds);
    }

    public function test_index_paginates_visible_servers_with_eight_servers_per_page(): void
    {
        $user = User::factory()->create();
        $servers = collect();

        foreach (range(1, 9) as $number) {
            $servers->push($this->createMinecraftServer($user, [
                'server_name' => sprintf('Target Server %02d', $number),
            ]));
        }

        $firstPageResponse = $this->actingAs($user)
            ->getJson(self::ROUTE.'?search=Target');
        $secondPageResponse = $this->actingAs($user)
            ->getJson(self::ROUTE.'?search=Target&page=2');

        $firstPageResponse->assertOk();
        $firstPageResponse->assertJsonCount(8, 'data');
        $firstPageResponse->assertJsonPath('current_page', 1);
        $firstPageResponse->assertJsonPath('last_page', 2);
        $firstPageResponse->assertJsonPath('per_page', 8);
        $firstPageResponse->assertJsonPath('total', 9);
        $this->assertSame(
            $servers->take(8)->pluck('id')->all(),
            array_column($firstPageResponse->json('data'), 'id')
        );
        $this->assertStringContainsString(
            'search=Target',
            $firstPageResponse->json('next_page_url')
        );

        $secondPageResponse->assertOk();
        $secondPageResponse->assertJsonCount(1, 'data');
        $secondPageResponse->assertJsonPath('data.0.id', $servers->last()->id);
        $secondPageResponse->assertJsonPath('current_page', 2);
        $secondPageResponse->assertJsonPath('last_page', 2);
        $secondPageResponse->assertJsonPath('per_page', 8);
        $secondPageResponse->assertJsonPath('total', 9);
    }

    public function test_index_orders_servers_by_name_and_then_by_id(): void
    {
        $user = User::factory()->create();
        $zuluServer = $this->createMinecraftServer($user, [
            'server_name' => 'Zulu Server',
        ]);
        $firstAlphaServer = $this->createMinecraftServer($user, [
            'server_name' => 'Alpha Server',
        ]);
        $secondAlphaServer = $this->createMinecraftServer($user, [
            'server_name' => 'Alpha Server',
        ]);
        $middleServer = $this->createMinecraftServer($user, [
            'server_name' => 'Middle Server',
        ]);

        $response = $this->actingAs($user)->getJson(self::ROUTE);

        $response->assertOk();
        $this->assertSame(
            [
                $firstAlphaServer->id,
                $secondAlphaServer->id,
                $middleServer->id,
                $zuluServer->id,
            ],
            array_column($response->json('data'), 'id')
        );
    }

    public function test_index_filters_visible_servers_by_partial_server_name(): void
    {
        $user = User::factory()->create();
        $otherOwner = User::factory()->create();

        $ownedMatch = $this->createMinecraftServer($user, [
            'server_name' => 'Survival Alpha',
        ]);
        $adminMatch = $this->createMinecraftServer($otherOwner, [
            'server_name' => 'Alpha Creative',
        ]);
        $adminMatch->admins()->attach($user->id);

        $visibleNameMismatch = $this->createMinecraftServer($user, [
            'server_name' => 'Beta Server',
            'motd' => 'Alpha only appears in the motd',
        ]);
        $hiddenMatch = $this->createMinecraftServer($otherOwner, [
            'server_name' => 'Alpha Hidden',
        ]);

        $response = $this->actingAs($user)->getJson(
            self::ROUTE.'?search='.urlencode('  Alpha  ')
        );

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('total', 2);
        $returnedServerIds = array_column($response->json('data'), 'id');
        $this->assertSame(
            [$adminMatch->id, $ownedMatch->id],
            $returnedServerIds
        );
        $this->assertNotContains($visibleNameMismatch->id, $returnedServerIds);
        $this->assertNotContains($hiddenMatch->id, $returnedServerIds);
    }

    public function test_index_returns_empty_paginated_list_when_search_does_not_match(): void
    {
        $user = User::factory()->create();
        $this->createMinecraftServer($user, [
            'server_name' => 'Survival Server',
        ]);

        $response = $this->actingAs($user)
            ->getJson(self::ROUTE.'?search=Creative');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('per_page', 8);
        $response->assertJsonPath('total', 0);
    }

    public function test_index_rejects_invalid_search_query_parameters(): void
    {
        $user = User::factory()->create();
        $oversizedSearch = str_repeat('a', 256);

        $oversizedResponse = $this->actingAs($user)->getJson(
            self::ROUTE.'?search='.$oversizedSearch
        );
        $arrayResponse = $this->actingAs($user)
            ->getJson(self::ROUTE.'?search[]=server');

        $oversizedResponse->assertUnprocessable();
        $oversizedResponse->assertJsonValidationErrors('search');
        $arrayResponse->assertUnprocessable();
        $arrayResponse->assertJsonValidationErrors('search');
    }

    public function test_index_accepts_search_at_the_maximum_allowed_length(): void
    {
        $user = User::factory()->create();
        $maximumLengthSearch = str_repeat('a', 255);

        $response = $this->actingAs($user)->getJson(
            self::ROUTE.'?search='.$maximumLengthSearch
        );

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_index_rejects_invalid_page_query_parameters(): void
    {
        $user = User::factory()->create();

        foreach (['0', '-1', '1.5', 'not-a-number'] as $page) {
            $response = $this->actingAs($user)->getJson(
                self::ROUTE.'?page='.urlencode($page)
            );

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('page');
        }

        $arrayResponse = $this->actingAs($user)
            ->getJson(self::ROUTE.'?page[]=1');

        $arrayResponse->assertUnprocessable();
        $arrayResponse->assertJsonValidationErrors('page');
    }

    public function test_search_treats_sql_injection_payload_as_plain_text(): void
    {
        $user = User::factory()->create();
        $this->createMinecraftServer($user, [
            'server_name' => 'Alpha Server',
        ]);
        $this->createMinecraftServer($user, [
            'server_name' => 'Beta Server',
        ]);
        $payload = "' OR 1=1 --";

        $response = $this->actingAs($user)->getJson(
            self::ROUTE.'?search='.urlencode($payload)
        );

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('total', 0);
    }

    public function test_index_returns_null_execution_slot_when_server_has_no_slot(): void
    {
        $user = User::factory()->create();
        $server = $this->createMinecraftServer($user);

        $response = $this->actingAs($user)->get(self::ROUTE);

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $server->id);
        $response->assertJsonPath('data.0.execution_slot', null);
        $response->assertJsonPath('data.0.version.id', $server->minecraft_version_id);
    }

    private function createMinecraftServer(User $owner, array $attributes = []): MinecraftServer
    {
        return MinecraftServer::factory()->for($owner, 'owner')->create(array_merge([
            'server_name' => 'Test Server',
            'motd' => 'Test motd',
            'difficulty' => 1,
            'force_gamemode' => true,
            'allow_flight' => false,
        ], $attributes));
    }

    private function serverFromResponse(array $servers, int $serverId): array
    {
        $matches = array_values(array_filter(
            $servers,
            fn (array $server): bool => $server['id'] === $serverId
        ));

        $this->assertCount(1, $matches);

        return $matches[0];
    }
}
