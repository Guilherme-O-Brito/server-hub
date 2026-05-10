<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->post('/user', [
            'name' => 'Joãozin do bet',
            'email' => 'joaozin@dobet.com',
            'password' => '123@Abcde',
            'is_admin' => false
        ]);

        $response->assertCreated();
        $response->assertJson(['message' => 'User created successfully']);
        $this->assertDatabaseHas('users', [
            'email' => 'joaozin@dobet.com'
        ]);
    }

    public function test_guest_cannot_create_user()
    {
        $response = $this->post('/user', []);

        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_create_user()
    {
        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($user)->post('/user', [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'Password@1234',
            'is_admin' => false
        ]);

        $response->assertStatus(403);
    }

    public function test_email_must_be_unique()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        User::factory()->create([
            'email' => 'test@test.com'
        ]);

        $response = $this->actingAs($admin)->post('/user', [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'Password@1234',
            'is_admin' => false
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_email_must_be_valid()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->post('/user', [
            'name' => 'test',
            'email' => 'test',
            'password' => 'Password@1234',
            'is_admin' => false
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_must_be_valid()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->post('/user', [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'password',
            'is_admin' => false
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_name_is_required()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->post('/user', [
            'name' => '',
            'email' => 'test@test.com',
            'password' => 'Password@1234',
            'is_admin' => false
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_email_is_required()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->post('/user', [
            'name' => 'Test',
            'email' => '',
            'password' => 'Password@1234',
            'is_admin' => false
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_is_required()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->post('/user', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '',
            'is_admin' => false
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_is_admin_is_required()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->post('/user', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'Password@1234',
        ]);

        $response->assertSessionHasErrors('is_admin');
    }

}
