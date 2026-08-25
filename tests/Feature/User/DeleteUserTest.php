<?php

namespace Tests\Feature\User;

use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->actingAs($admin)->delete("/admin/user/{$user->id}");

        $response->assertOk();
        $response->assertJson(['message' => 'User successfully deleted']);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);

    }

    public function test_admin_cannot_delete_user_who_still_owns_minecraft_servers(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);
        $user = User::factory()->create([
            'is_admin' => false,
        ]);
        $minecraftServer = MinecraftServer::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($admin)->delete("/admin/user/{$user->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'User cannot be deleted while they still own Minecraft servers.',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
        $this->assertDatabaseHas('minecraft_servers', [
            'id' => $minecraftServer->id,
            'owner_id' => $user->id,
        ]);
    }

    public function test_guest_cannot_delete_user()
    {   
        $user = User::factory()->create([
            'is_admin' => false
        ]);

        $response = $this->delete("/admin/user/{$user->id}");

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

        $response = $this->actingAs($user)->delete("/admin/user/{$userToDelete->id}");

        $response->assertForbidden();
    }

    public function test_cannot_delete_nonexistent_user()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->delete('/admin/user/999');

        $response->assertNotFound();
    }

    public function test_admin_cannot_delete_itself()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $response = $this->actingAs($admin)->delete("/admin/user/{$admin->id}");

        $response->assertForbidden();
        $response->assertJson(['message' => 'Are you dumb?']);
        $this->assertDatabaseHas('users', [
            'id' => $admin->id
        ]);

    }

}
