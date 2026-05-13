<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_user()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($admin)->delete("/user/{$user->id}");

        $response->assertOk();
        $response->assertJson(['message' => 'User successfully deleted']);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);

    }

    public function test_guest_cannot_delete_user()
    {   
        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->delete("/user/{$user->id}");

        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_delete_user()
    {
        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $userToDelete = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->actingAs($user)->delete("/user/{$userToDelete->id}");

        $response->assertForbidden();
    }

    public function test_cannot_delete_nonexistent_user()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->delete('/user/999');

        $response->assertNotFound();
    }

    public function test_admin_cannot_delete_itself()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->delete("/user/{$admin->id}");

        $response->assertForbidden();
        $response->assertJson(['message' => 'Are you dumb?']);
        $this->assertDatabaseHas('users', [
            'id' => $admin->id
        ]);

    }

}
