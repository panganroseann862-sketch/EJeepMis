<?php

namespace Tests\Feature\Driver;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_view_notifications_list(): void
    {
        $driver = User::factory()->driver()->create();
        
        // Create some notifications for the driver
        Notification::factory()->count(3)->create([
            'user_id' => $driver->id,
            'is_read' => false,
        ]);
        
        Notification::factory()->count(2)->create([
            'user_id' => $driver->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($driver)->get(route('driver.notifications.index'));

        $response->assertStatus(200);
        $response->assertViewIs('driver.notifications.index');
        $response->assertViewHas('notifications');
        
        // Should see all 5 notifications
        $notifications = $response->viewData('notifications');
        $this->assertCount(5, $notifications);
    }

    public function test_driver_only_sees_their_own_notifications(): void
    {
        $driver1 = User::factory()->driver()->create();
        $driver2 = User::factory()->driver()->create();
        
        // Create notifications for driver1
        Notification::factory()->count(3)->create([
            'user_id' => $driver1->id,
        ]);
        
        // Create notifications for driver2
        Notification::factory()->count(2)->create([
            'user_id' => $driver2->id,
        ]);

        $response = $this->actingAs($driver1)->get(route('driver.notifications.index'));

        $response->assertStatus(200);
        
        // Driver1 should only see their 3 notifications
        $notifications = $response->viewData('notifications');
        $this->assertCount(3, $notifications);
        
        foreach ($notifications as $notification) {
            $this->assertEquals($driver1->id, $notification->user_id);
        }
    }

    public function test_notifications_are_ordered_by_newest_first(): void
    {
        $driver = User::factory()->driver()->create();
        
        // Create notifications at different times
        $oldest = Notification::factory()->create([
            'user_id' => $driver->id,
            'created_at' => now()->subDays(3),
        ]);
        
        $middle = Notification::factory()->create([
            'user_id' => $driver->id,
            'created_at' => now()->subDays(1),
        ]);
        
        $newest = Notification::factory()->create([
            'user_id' => $driver->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($driver)->get(route('driver.notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->viewData('notifications');
        
        // Should be ordered newest first
        $this->assertEquals($newest->id, $notifications[0]->id);
        $this->assertEquals($middle->id, $notifications[1]->id);
        $this->assertEquals($oldest->id, $notifications[2]->id);
    }

    public function test_driver_can_mark_notification_as_read(): void
    {
        $driver = User::factory()->driver()->create();
        
        $notification = Notification::factory()->create([
            'user_id' => $driver->id,
            'is_read' => false,
            'read_at' => null,
        ]);

        $response = $this->actingAs($driver)
            ->postJson(route('driver.notifications.mark-as-read', $notification));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
        
        // Verify the notification was marked as read
        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    public function test_driver_cannot_mark_another_drivers_notification_as_read(): void
    {
        $driver1 = User::factory()->driver()->create();
        $driver2 = User::factory()->driver()->create();
        
        $notification = Notification::factory()->create([
            'user_id' => $driver2->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($driver1)
            ->postJson(route('driver.notifications.mark-as-read', $notification));

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Unauthorized',
        ]);
        
        // Verify the notification was NOT marked as read
        $notification->refresh();
        $this->assertFalse($notification->is_read);
    }

    public function test_unread_notifications_are_highlighted(): void
    {
        $driver = User::factory()->driver()->create();
        
        $unreadNotification = Notification::factory()->create([
            'user_id' => $driver->id,
            'is_read' => false,
            'title' => 'Unread Notification',
        ]);
        
        $readNotification = Notification::factory()->create([
            'user_id' => $driver->id,
            'is_read' => true,
            'title' => 'Read Notification',
        ]);

        $response = $this->actingAs($driver)->get(route('driver.notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('bg-blue-50'); // Unread background color
        $response->assertSee('New'); // Unread badge
    }

    public function test_notification_displays_type_title_and_message(): void
    {
        $driver = User::factory()->driver()->create();
        
        $notification = Notification::factory()->create([
            'user_id' => $driver->id,
            'type' => 'route_update',
            'title' => 'Route Changed',
            'message' => 'Your assigned route has been updated.',
        ]);

        $response = $this->actingAs($driver)->get(route('driver.notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Route Changed');
        $response->assertSee('Your assigned route has been updated.');
    }

    public function test_notification_displays_timestamp(): void
    {
        $driver = User::factory()->driver()->create();
        
        $notification = Notification::factory()->create([
            'user_id' => $driver->id,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($driver)->get(route('driver.notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('2 hours ago');
    }

    public function test_admin_cannot_access_driver_notifications(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('driver.notifications.index'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->get(route('driver.notifications.index'));

        $response->assertRedirect(route('login'));
    }
}
