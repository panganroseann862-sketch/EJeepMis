<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_admin_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'username' => 'adminuser',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'username' => 'adminuser',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_driver_redirected_to_driver_dashboard(): void
    {
        $driver = User::factory()->create([
            'username' => 'driveruser',
            'password' => bcrypt('password'),
            'role' => 'driver',
        ]);

        $response = $this->post('/login', [
            'username' => 'driveruser',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('driver.dashboard'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_driver_cannot_access_admin_dashboard(): void
    {
        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $response = $this->actingAs($driver)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_driver_can_access_driver_dashboard(): void
    {
        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $response = $this->actingAs($driver)->get(route('driver.dashboard'));

        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_driver_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('driver.dashboard'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_users_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }
}
