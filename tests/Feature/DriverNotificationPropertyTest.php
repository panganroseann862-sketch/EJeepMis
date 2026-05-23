<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverNotificationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 56: Unread notifications displayed
     * 
     * For any driver with notifications where is_read = false, 
     * those notifications should appear on their dashboard.
     * 
     * Validates: Requirements 10.4
     */
    public function test_property_56_unread_notifications_displayed(): void
    {
        // Test with varying numbers of unread and read notifications
        $testCases = [
            ['unread' => 0, 'read' => 0],
            ['unread' => 1, 'read' => 0],
            ['unread' => 5, 'read' => 0],
            ['unread' => 0, 'read' => 3],
            ['unread' => 3, 'read' => 2],
            ['unread' => 10, 'read' => 5],
        ];

        foreach ($testCases as $case) {
            $driver = User::factory()->driver()->create();
            
            // Create unread notifications
            $unreadNotifications = Notification::factory()
                ->count($case['unread'])
                ->create([
                    'user_id' => $driver->id,
                    'is_read' => false,
                    'read_at' => null,
                ]);
            
            // Create read notifications
            Notification::factory()
                ->count($case['read'])
                ->create([
                    'user_id' => $driver->id,
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            $response = $this->actingAs($driver)->get(route('driver.notifications.index'));

            $response->assertStatus(200);
            
            // Get all notifications from the view
            $notifications = $response->viewData('notifications');
            
            // Count unread notifications in the response
            $unreadCount = $notifications->filter(fn($n) => !$n->is_read)->count();
            
            // Verify all unread notifications are displayed
            $this->assertEquals(
                $case['unread'], 
                $unreadCount,
                "Expected {$case['unread']} unread notifications, got {$unreadCount}"
            );
            
            // Verify each unread notification is present
            foreach ($unreadNotifications as $notification) {
                $found = $notifications->contains('id', $notification->id);
                $this->assertTrue(
                    $found,
                    "Unread notification {$notification->id} should be displayed"
                );
            }
        }
    }

    /**
     * Property 57: Viewing notification marks as read
     * 
     * For any notification, when a driver views it, is_read should be set to true 
     * and read_at should be set to the current timestamp.
     * 
     * Validates: Requirements 10.5
     */
    public function test_property_57_viewing_notification_marks_as_read(): void
    {
        // Test with different notification types and states
        $notificationTypes = ['route_update', 'schedule_change', 'capacity_warning'];
        
        foreach ($notificationTypes as $type) {
            $driver = User::factory()->driver()->create();
            
            $notification = Notification::factory()->create([
                'user_id' => $driver->id,
                'type' => $type,
                'is_read' => false,
                'read_at' => null,
            ]);

            // Record time before marking as read (with buffer for timing precision)
            $beforeTime = now()->subSecond();
            
            $response = $this->actingAs($driver)
                ->postJson(route('driver.notifications.mark-as-read', $notification));

            $response->assertStatus(200);
            
            // Refresh the notification from database
            $notification->refresh();
            
            // Verify is_read is set to true
            $this->assertTrue(
                $notification->is_read,
                "Notification should be marked as read"
            );
            
            // Verify read_at is set to a timestamp
            $this->assertNotNull(
                $notification->read_at,
                "read_at should be set to current timestamp"
            );
            
            // Verify read_at is recent (within reasonable time window)
            $this->assertTrue(
                $notification->read_at->greaterThanOrEqualTo($beforeTime),
                "read_at should be set to current time or later"
            );
            
            $this->assertTrue(
                $notification->read_at->lessThanOrEqualTo(now()->addSeconds(3)),
                "read_at should not be in the future"
            );
        }
    }

    /**
     * Property 58: Notification history maintained
     * 
     * For any driver, all notifications created for them should be retrievable 
     * from their notification history, regardless of read status.
     * 
     * Validates: Requirements 10.6
     */
    public function test_property_58_notification_history_maintained(): void
    {
        // Test with varying notification counts and read statuses
        $testCases = [
            ['total' => 1, 'read_count' => 0],
            ['total' => 5, 'read_count' => 0],
            ['total' => 5, 'read_count' => 5],
            ['total' => 10, 'read_count' => 3],
            ['total' => 20, 'read_count' => 15],
        ];

        foreach ($testCases as $case) {
            $driver = User::factory()->driver()->create();
            
            $allNotifications = collect();
            
            // Create read notifications
            for ($i = 0; $i < $case['read_count']; $i++) {
                $notification = Notification::factory()->create([
                    'user_id' => $driver->id,
                    'is_read' => true,
                    'read_at' => now()->subDays(rand(1, 30)),
                ]);
                $allNotifications->push($notification);
            }
            
            // Create unread notifications
            $unreadCount = $case['total'] - $case['read_count'];
            for ($i = 0; $i < $unreadCount; $i++) {
                $notification = Notification::factory()->create([
                    'user_id' => $driver->id,
                    'is_read' => false,
                    'read_at' => null,
                ]);
                $allNotifications->push($notification);
            }

            $response = $this->actingAs($driver)->get(route('driver.notifications.index'));

            $response->assertStatus(200);
            
            // Get notifications from the view
            $displayedNotifications = $response->viewData('notifications');
            
            // Verify total count matches
            $this->assertEquals(
                $case['total'],
                $displayedNotifications->total(),
                "All {$case['total']} notifications should be retrievable"
            );
            
            // Verify each notification is present in history
            foreach ($allNotifications as $notification) {
                $found = $displayedNotifications->contains('id', $notification->id);
                $this->assertTrue(
                    $found,
                    "Notification {$notification->id} (read: {$notification->is_read}) should be in history"
                );
            }
            
            // Verify both read and unread notifications are present
            $displayedReadCount = $displayedNotifications->filter(fn($n) => $n->is_read)->count();
            $displayedUnreadCount = $displayedNotifications->filter(fn($n) => !$n->is_read)->count();
            
            $this->assertEquals(
                $case['read_count'],
                $displayedReadCount,
                "Should have {$case['read_count']} read notifications in history"
            );
            
            $this->assertEquals(
                $unreadCount,
                $displayedUnreadCount,
                "Should have {$unreadCount} unread notifications in history"
            );
        }
    }

    /**
     * Additional property test: Driver only sees their own notifications
     * 
     * Ensures notification isolation between drivers
     */
    public function test_property_driver_notification_isolation(): void
    {
        $driver1 = User::factory()->driver()->create();
        $driver2 = User::factory()->driver()->create();
        
        // Create notifications for driver1
        $driver1Notifications = Notification::factory()
            ->count(5)
            ->create(['user_id' => $driver1->id]);
        
        // Create notifications for driver2
        $driver2Notifications = Notification::factory()
            ->count(3)
            ->create(['user_id' => $driver2->id]);

        // Driver1 should only see their notifications
        $response1 = $this->actingAs($driver1)->get(route('driver.notifications.index'));
        $notifications1 = $response1->viewData('notifications');
        
        $this->assertEquals(5, $notifications1->total());
        foreach ($driver1Notifications as $notification) {
            $this->assertTrue($notifications1->contains('id', $notification->id));
        }
        foreach ($driver2Notifications as $notification) {
            $this->assertFalse($notifications1->contains('id', $notification->id));
        }

        // Driver2 should only see their notifications
        $response2 = $this->actingAs($driver2)->get(route('driver.notifications.index'));
        $notifications2 = $response2->viewData('notifications');
        
        $this->assertEquals(3, $notifications2->total());
        foreach ($driver2Notifications as $notification) {
            $this->assertTrue($notifications2->contains('id', $notification->id));
        }
        foreach ($driver1Notifications as $notification) {
            $this->assertFalse($notifications2->contains('id', $notification->id));
        }
    }

    /**
     * Additional property test: Cannot mark another driver's notification as read
     * 
     * Ensures authorization is enforced
     */
    public function test_property_notification_authorization_enforced(): void
    {
        $driver1 = User::factory()->driver()->create();
        $driver2 = User::factory()->driver()->create();
        
        $notification = Notification::factory()->create([
            'user_id' => $driver2->id,
            'is_read' => false,
        ]);

        // Driver1 attempts to mark driver2's notification as read
        $response = $this->actingAs($driver1)
            ->postJson(route('driver.notifications.mark-as-read', $notification));

        $response->assertStatus(403);
        
        // Verify notification was NOT marked as read
        $notification->refresh();
        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }
}
