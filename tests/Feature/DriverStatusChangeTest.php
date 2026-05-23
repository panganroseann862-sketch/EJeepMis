<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverStatusChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_view_status_change_form(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('driver.status.change'));

        $response->assertOk();
        $response->assertSee('Change Your Status');
    }

    public function test_driver_can_change_status_to_inactive(): void
    {
        $driver = User::factory()->driver()->create(['status' => 'active']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($driver)->post(route('driver.status.update'), [
            'status' => 'inactive',
            'reason' => 'Taking a day off for personal reasons',
        ]);

        $response->assertRedirect(route('driver.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $driver->id,
            'status' => 'inactive',
        ]);

        // Check that admin was notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'status_change',
        ]);
    }

    public function test_driver_can_change_status_to_active(): void
    {
        $driver = User::factory()->driver()->create(['status' => 'inactive']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($driver)->post(route('driver.status.update'), [
            'status' => 'active',
            'reason' => 'Ready to resume work after rest',
        ]);

        $response->assertRedirect(route('driver.dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $driver->id,
            'status' => 'active',
        ]);
    }

    public function test_status_change_validates_required_fields(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->post(route('driver.status.update'), [
            'status' => '',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors(['status', 'reason']);
    }

    public function test_status_change_validates_reason_minimum_length(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->post(route('driver.status.update'), [
            'status' => 'inactive',
            'reason' => 'short',
        ]);

        $response->assertSessionHasErrors(['reason']);
    }

    public function test_admin_can_view_notifications(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        Notification::create([
            'user_id' => $admin->id,
            'type' => 'status_change',
            'title' => 'Driver Status Change Request',
            'message' => "{$driver->first_name} {$driver->last_name} changed status to inactive",
            'data' => [
                'driver_id' => $driver->id,
                'driver_name' => "{$driver->first_name} {$driver->last_name}",
                'old_status' => 'active',
                'new_status' => 'inactive',
                'reason' => 'Personal reasons',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee('Driver Status Change Request');
    }

    public function test_admin_can_reply_to_status_change_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $notification = Notification::create([
            'user_id' => $admin->id,
            'type' => 'status_change',
            'title' => 'Driver Status Change Request',
            'message' => "{$driver->first_name} {$driver->last_name} changed status to inactive",
            'data' => [
                'driver_id' => $driver->id,
                'driver_name' => "{$driver->first_name} {$driver->last_name}",
                'old_status' => 'active',
                'new_status' => 'inactive',
                'reason' => 'Personal reasons',
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.notifications.reply', $notification), [
            'message' => 'Thank you for informing us. Take care!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check that reply notification was created for driver
        $this->assertDatabaseHas('notifications', [
            'user_id' => $driver->id,
            'parent_id' => $notification->id,
            'type' => 'admin_reply',
        ]);

        // Check that original notification was marked as read
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    public function test_admin_cannot_reply_to_other_admins_notification(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $notification = Notification::create([
            'user_id' => $admin2->id,
            'type' => 'status_change',
            'title' => 'Driver Status Change Request',
            'message' => 'Test',
            'data' => ['driver_id' => $driver->id],
        ]);

        $response = $this->actingAs($admin1)->post(route('admin.notifications.reply', $notification), [
            'message' => 'This should not work',
        ]);

        $response->assertForbidden();
    }

    public function test_driver_cannot_access_admin_notifications(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('admin.notifications.index'));

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_driver_status_change_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('driver.status.change'));

        $response->assertForbidden();
    }
}
