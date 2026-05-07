<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {   

        $user = User::factory()->create([
            'password' => '12345'
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '12345'
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    public function test_user_cannot_login_with_wrong_password()
    {
        $user = User::factory()->create([
            'password' => '12345'
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '123'
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_user_cannot_login_with_invalid_email()
    {
        $response = $this->post('/login', [
            'email' => 'fake@email.com',
            'password' => '123'
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_login_requires_email_and_password()
    {
        $response = $this->post('/login', []);
        
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_authenticated_user_cannot_access_login_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertStatus(302);
    }

    public function test_guest_can_access_login_view()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

}
