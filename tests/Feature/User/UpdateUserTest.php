<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

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

        $response = $this->actingAs($admin)->put("/admin/user/{$user->id}", [
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

    public function test_changing_password_removes_only_affected_user_sessions()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $otherUser = User::factory()->create();

        $this->createSession('affected-session-1', $user);
        $this->createSession('affected-session-2', $user);
        $this->createSession('admin-session', $admin);
        $this->createSession('other-user-session', $otherUser);

        $response = $this->actingAs($admin)->put("/admin/user/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'NewPassword@1234',
            'is_admin' => false
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => 'affected-session-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'affected-session-2']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-user-session']);
    }

    public function test_admin_changing_own_password_removes_own_sessions()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $otherUser = User::factory()->create();

        $this->createSession('current-admin-session', $admin);
        $this->createSession('another-admin-session', $admin);
        $this->createSession('other-user-session', $otherUser);

        $response = $this->actingAs($admin)->put("/admin/user/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => 'NewPassword@1234',
            'is_admin' => true
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => 'current-admin-session']);
        $this->assertDatabaseMissing('sessions', ['id' => 'another-admin-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-user-session']);
    }

    public function test_guest_cannot_update_user()
    {   
        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->put("/admin/user/{$user->id}", []);

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

        $response = $this->actingAs($user)->put("/admin/user/{$userToUpdate->id}", [
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

        $response = $this->actingAs($admin)->put("/admin/user/{$userToUpdate->id}", [
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

        $response = $this->actingAs($admin)->put("/admin/user/{$userToUpdate->id}", [
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

        $response = $this->actingAs($admin)->put("/admin/user/{$userToUpdate->id}", [
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

        $response = $this->actingAs($admin)->put("/admin/user/{$userToUpdate->id}", [
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

        $response = $this->actingAs($admin)->put("/admin/user/{$userToUpdate->id}", [
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

        $response = $this->actingAs($admin)->put("/admin/user/{$userToUpdate->id}", [
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

        $response = $this->actingAs($admin)->put("/admin/user/{$userToUpdate->id}", [
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

        $response = $this->actingAs($admin)->put('/admin/user/999', [
            'name' => 'Teste',
            'email' => 'teste@test.com',
            'password' => 'Password@1234',
            'is_admin' => false
        ]);

        $response->assertNotFound();
    }

    public function test_password_and_sessions_remain_the_same_when_password_is_not_provided()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('OldPassword@123')
        ]);

        $oldPasswordHash = $user->password;

        $this->createSession('user-session', $user);

        $this->actingAs($admin)->put("/admin/user/{$user->id}", [
            'name' => 'Novo Nome',
            'email' => 'novo@test.com',
            'password' => null,
            'is_admin' => false
        ]);

        $user->refresh();

        $this->assertEquals($oldPasswordHash, $user->password);
        $this->assertDatabaseHas('sessions', ['id' => 'user-session']);
    }

    public function test_user_can_keep_same_email()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $user = User::factory()->create([
            'email' => 'test@test.com'
        ]);

        $response = $this->actingAs($admin)->put("/admin/user/{$user->id}", [
            'name' => 'Novo Nome',
            'email' => 'test@test.com',
            'password' => null,
            'is_admin' => false
        ]);

        $response->assertOk();
    }

    private function createSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-session-payload',
            'last_activity' => now()->timestamp,
        ]);
    }

}
