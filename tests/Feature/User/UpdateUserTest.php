<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($admin)->put("/user/{$user->id}", [
            'name' => 'Joãozin do bet',
            'email' => 'joaozin@dobet.com',
            'password' => '123@Abcde',
            'is_admin' => true
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'User successfully modified']);
        $this->assertDatabaseHas('users', [
            'name' => 'Joãozin do bet',
            'email' => 'joaozin@dobet.com',
            'is_admin' => true
        ]);

        $user->refresh();

        $this->assertTrue(Hash::check('123@Abcde', $user->password));

    }

    public function test_guest_cannot_update_user()
    {   
        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->put("/user/{$user->id}", []);

        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_update_user()
    {
        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $userToUpdate = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($user)->put("/user/{$userToUpdate->id}", [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => null,
            'is_admin' => false
        ]);

        $response->assertStatus(403);
    }

    public function test_email_must_be_unique()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $userToUpdate = User::factory()->create([
            'is_admin' => false
        ]);

        User::factory()->create([
            'email' => 'test@test.com'
        ]);

        $response = $this->actingAs($admin)->put("/user/{$userToUpdate->id}", [
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

        $userToUpdate = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($admin)->put("/user/{$userToUpdate->id}", [
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

        $userToUpdate = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($admin)->put("/user/{$userToUpdate->id}", [
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

        $userToUpdate = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($admin)->put("/user/{$userToUpdate->id}", [
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

        $userToUpdate = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($admin)->put("/user/{$userToUpdate->id}", [
            'name' => 'Test',
            'email' => '',
            'password' => 'Password@1234',
            'is_admin' => false
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_is_not_required()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $userToUpdate = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($admin)->put("/user/{$userToUpdate->id}", [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => null,
            'is_admin' => false
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'User successfully modified']);
        $this->assertDatabaseHas('users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'is_admin' => false
        ]);
    }

    public function test_is_admin_is_required()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $userToUpdate = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($admin)->put("/user/{$userToUpdate->id}", [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'Password@1234',
        ]);

        $response->assertSessionHasErrors('is_admin');
    }

    public function test_cannot_update_nonexistent_user()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->put('/user/999', [
            'name' => 'Teste',
            'email' => 'teste@test.com',
            'password' => 'Password@1234',
            'is_admin' => false
        ]);

        $response->assertNotFound();
    }

    public function test_password_remains_the_same_when_not_provided()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('OldPassword@123')
        ]);

        $oldPasswordHash = $user->password;

        $this->actingAs($admin)->put("/user/{$user->id}", [
            'name' => 'Novo Nome',
            'email' => 'novo@test.com',
            'password' => null,
            'is_admin' => false
        ]);

        $user->refresh();

        $this->assertEquals($oldPasswordHash, $user->password);
    }

    public function test_user_can_keep_same_email()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $user = User::factory()->create([
            'email' => 'test@test.com'
        ]);

        $response = $this->actingAs($admin)->put("/user/{$user->id}", [
            'name' => 'Novo Nome',
            'email' => 'test@test.com',
            'password' => null,
            'is_admin' => false
        ]);

        $response->assertOk();
    }

}
