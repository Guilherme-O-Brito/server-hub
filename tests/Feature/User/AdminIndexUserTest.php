<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIndexUserTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/admin/user';

    public function test_guest_cannot_access_admin_index(): void
    {
        User::factory()->create();

        $response = $this->get(self::ROUTE);

        $response->assertRedirect('/login');
    }

    public function test_authenticated_non_admin_cannot_access_admin_index(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->getJson(self::ROUTE);

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_index_without_optional_query_parameters(): void
    {
        $admin = User::factory()->create([
            'name' => 'Authenticated Admin',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->getJson(self::ROUTE);

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $admin->id);
        $response->assertJsonPath('data.0.name', 'Authenticated Admin');
        $response->assertJsonPath('current_page', 1);
    }

    public function test_admin_index_returns_all_unprotected_user_columns_and_hides_credentials(): void
    {
        $admin = User::factory()->create([
            'name' => 'Authenticated Admin',
            'is_admin' => true,
        ]);
        $listedUser = User::factory()->create([
            'name' => 'Listed User',
            'email' => 'listed@example.com',
            'password' => 'AnotherSecretPasswordHash',
            'remember_token' => 'another-secret-token',
            'is_admin' => false,
        ]);

        $response = $this->actingAs($admin)->getJson(self::ROUTE.'?search=Listed');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonStructure([
            'current_page',
            'data' => [[
                'id',
                'name',
                'email',
                'email_verified_at',
                'is_admin',
                'created_at',
                'updated_at',
            ]],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        $returnedUser = $response->json('data.0');

        $this->assertEqualsCanonicalizing(
            [
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
                'is_admin',
            ],
            array_keys($returnedUser)
        );
        $this->assertSame($listedUser->id, $returnedUser['id']);
        $this->assertSame('Listed User', $returnedUser['name']);
        $this->assertSame('listed@example.com', $returnedUser['email']);
        $this->assertFalse($returnedUser['is_admin']);
        $response->assertJsonMissingPath('data.0.password');
        $response->assertJsonMissingPath('data.0.remember_token');
    }

    public function test_admin_index_returns_an_empty_paginated_response_when_no_users_match(): void
    {
        $admin = User::factory()->create([
            'name' => 'Authenticated Admin',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->getJson(self::ROUTE.'?search=NoMatch');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('per_page', 5);
        $response->assertJsonPath('total', 0);
    }

    public function test_admin_index_filters_users_by_partial_name(): void
    {
        $admin = User::factory()->create([
            'name' => 'Authenticated Admin',
            'is_admin' => true,
        ]);
        $anna = User::factory()->create(['name' => 'Anna']);
        $joanna = User::factory()->create(['name' => 'Joanna']);
        User::factory()->create(['name' => 'Robert']);

        $response = $this->actingAs($admin)->getJson(self::ROUTE.'?search=anna');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $this->assertSame(
            [$anna->id, $joanna->id],
            array_column($response->json('data'), 'id')
        );
    }

    public function test_admin_index_orders_users_by_name_and_then_by_id(): void
    {
        $admin = User::factory()->create([
            'name' => 'Zulu Admin',
            'is_admin' => true,
        ]);
        $firstSameNameUser = User::factory()->create(['name' => 'Alex']);
        $secondSameNameUser = User::factory()->create(['name' => 'Alex']);
        $middleUser = User::factory()->create(['name' => 'Maria']);

        $response = $this->actingAs($admin)->getJson(self::ROUTE);

        $response->assertOk();
        $this->assertSame(
            [
                $firstSameNameUser->id,
                $secondSameNameUser->id,
                $middleUser->id,
                $admin->id,
            ],
            array_column($response->json('data'), 'id')
        );
    }

    public function test_admin_index_paginates_five_users_per_page_and_preserves_search_query(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $users = collect([
            User::factory()->create(['name' => 'Target Alpha']),
            User::factory()->create(['name' => 'Target Bravo']),
            User::factory()->create(['name' => 'Target Charlie']),
            User::factory()->create(['name' => 'Target Delta']),
            User::factory()->create(['name' => 'Target Echo']),
            User::factory()->create(['name' => 'Target Foxtrot']),
        ]);

        $firstPageResponse = $this->actingAs($admin)
            ->getJson(self::ROUTE.'?search=Target');
        $secondPageResponse = $this->actingAs($admin)
            ->getJson(self::ROUTE.'?search=Target&page=2');

        $firstPageResponse->assertOk();
        $firstPageResponse->assertJsonCount(5, 'data');
        $firstPageResponse->assertJsonPath('current_page', 1);
        $firstPageResponse->assertJsonPath('last_page', 2);
        $firstPageResponse->assertJsonPath('per_page', 5);
        $firstPageResponse->assertJsonPath('total', 6);
        $this->assertStringContainsString(
            'search=Target',
            $firstPageResponse->json('next_page_url')
        );

        $secondPageResponse->assertOk();
        $secondPageResponse->assertJsonCount(1, 'data');
        $secondPageResponse->assertJsonPath('current_page', 2);
        $secondPageResponse->assertJsonPath('total', 6);

        $returnedIds = array_merge(
            array_column($firstPageResponse->json('data'), 'id'),
            array_column($secondPageResponse->json('data'), 'id')
        );

        $this->assertEqualsCanonicalizing($users->pluck('id')->all(), $returnedIds);
    }

    public function test_admin_index_rejects_invalid_page_query_parameters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['0', '-1', '1.5', 'not-a-number'] as $page) {
            $response = $this->actingAs($admin)->getJson(
                self::ROUTE.'?page='.urlencode($page)
            );

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('page');
        }

        $arrayResponse = $this->actingAs($admin)->getJson(self::ROUTE.'?page[]=1');

        $arrayResponse->assertUnprocessable();
        $arrayResponse->assertJsonValidationErrors('page');
    }

    public function test_admin_index_rejects_invalid_search_query_parameters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $oversizedSearch = str_repeat('a', 256);

        $oversizedResponse = $this->actingAs($admin)->getJson(
            self::ROUTE.'?search='.$oversizedSearch
        );
        $arrayResponse = $this->actingAs($admin)->getJson(self::ROUTE.'?search[]=name');

        $oversizedResponse->assertUnprocessable();
        $oversizedResponse->assertJsonValidationErrors('search');
        $arrayResponse->assertUnprocessable();
        $arrayResponse->assertJsonValidationErrors('search');
    }

    public function test_admin_index_accepts_search_at_the_maximum_allowed_length(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $maximumLengthSearch = str_repeat('a', 255);

        $response = $this->actingAs($admin)->getJson(
            self::ROUTE.'?search='.$maximumLengthSearch
        );

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_admin_index_ignores_unknown_query_parameters_without_exposing_protected_columns(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create([
            'name' => 'Visible User',
            'email' => 'visible@example.com',
            'remember_token' => 'secret-token',
            'is_admin' => false,
        ]);

        $response = $this->actingAs($admin)->getJson(
            self::ROUTE.'?search=Visible&columns[]=password&per_page=999999'
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('per_page', 5);
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
                'is_admin',
            ],
            array_keys($response->json('data.0'))
        );
        $response->assertJsonMissingPath('data.0.password');
        $response->assertJsonMissingPath('data.0.remember_token');
    }

    public function test_admin_index_treats_sql_injection_payload_as_plain_text(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        $payload = "' OR 1=1 --";

        $response = $this->actingAs($admin)->getJson(
            self::ROUTE.'?search='.urlencode($payload)
        );

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('total', 0);
    }
}
