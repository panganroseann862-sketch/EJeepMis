<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_drivers_list(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($admin)->get(route('admin.drivers.index'));

        $response->assertOk()
            ->assertSee($driver->first_name)
            ->assertSee($driver->last_name)
            ->assertSee($driver->username);
    }

    public function test_admin_can_view_create_driver_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.drivers.create'));

        $response->assertOk()
            ->assertSee('Add New Driver')
            ->assertSee('Username')
            ->assertSee('Email')
            ->assertSee('Password');
    }

    public function test_admin_can_create_driver(): void
    {
        $admin = User::factory()->admin()->create();

        $driverData = [
            'username' => 'test_driver',
            'email' => 'driver@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
        ];

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), $driverData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'username' => 'test_driver',
            'email' => 'driver@example.com',
            'role' => 'driver',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_driver_details(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($admin)->get(route('admin.drivers.show', $driver));

        $response->assertOk()
            ->assertSee($driver->first_name)
            ->assertSee($driver->last_name)
            ->assertSee($driver->email)
            ->assertSee('Performance Metrics');
    }

    public function test_admin_can_view_edit_driver_form(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($admin)->get(route('admin.drivers.edit', $driver));

        $response->assertOk()
            ->assertSee('Edit Driver')
            ->assertSee($driver->username)
            ->assertSee($driver->email);
    }

    public function test_admin_can_update_driver(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $updateData = [
            'username' => $driver->username,
            'email' => 'updated@example.com',
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '9876543210',
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->put(route('admin.drivers.update', $driver), $updateData);

        $response->assertRedirect(route('admin.drivers.show', $driver));
        $this->assertDatabaseHas('users', [
            'id' => $driver->id,
            'email' => 'updated@example.com',
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    public function test_admin_can_delete_driver(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($admin)->delete(route('admin.drivers.destroy', $driver));

        $response->assertRedirect(route('admin.drivers.index'));
        $this->assertDatabaseMissing('users', [
            'id' => $driver->id,
        ]);
    }

    public function test_driver_cannot_access_driver_management(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('admin.drivers.index'));

        $response->assertForbidden();
    }

    public function test_create_driver_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), []);

        $response->assertSessionHasErrors(['username', 'email', 'password', 'first_name', 'last_name']);
    }

    public function test_create_driver_validates_unique_username(): void
    {
        $admin = User::factory()->admin()->create();
        $existingDriver = User::factory()->driver()->create(['username' => 'existing_user']);

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), [
            'username' => 'existing_user',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_create_driver_validates_unique_email(): void
    {
        $admin = User::factory()->admin()->create();
        $existingDriver = User::factory()->driver()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), [
            'username' => 'new_user',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_create_driver_validates_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), [
            'username' => 'test_driver',
            'email' => 'driver@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_create_driver_validates_password_minimum_length(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), [
            'username' => 'test_driver',
            'email' => 'driver@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_update_driver_allows_empty_password(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $originalPassword = $driver->password;

        $response = $this->actingAs($admin)->put(route('admin.drivers.update', $driver), [
            'username' => $driver->username,
            'email' => $driver->email,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $driver->refresh();
        $this->assertEquals($originalPassword, $driver->password);
    }

    public function test_driver_show_displays_performance_metrics(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($admin)->get(route('admin.drivers.show', $driver));

        $response->assertOk()
            ->assertSee('Completed Trips')
            ->assertSee('Schedule Adherence')
            ->assertSee('Average Passenger Load');
    }
}
