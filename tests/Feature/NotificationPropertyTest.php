<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\Notification;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Notification System
 * 
 * These tests validate universal correctness properties for the notification
 * system across multiple randomly generated inputs to ensure notifications
 * are created and delivered correctly for all valid scenarios.
 */
class NotificationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 53: Route updates create notifications
     * Validates: Requirements 10.1
     * 
     * For any route modification affecting a driver's schedule, a notification 
     * should be created for that driver with type 'route_update'.
     */
    public function test_property_route_updates_create_notifications(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $admin = User::factory()->admin()->create();
            $route = Route::factory()->create([
                'route_code' => 'R' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT) . substr(md5(uniqid()), 0, 6),
            ]);
            
            // Create random number of drivers with schedules on this route
            $driverCount = fake()->numberBetween(1, 4);
            $drivers = [];
            
            for ($j = 0; $j < $driverCount; $j++) {
                $driver = User::factory()->driver()->create();
                $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
                
                Schedule::factory()->create([
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'status' => 'active',
                ]);
                
                $drivers[] = $driver;
            }
            
            // Update the route
            $newRouteName = 'Updated Route ' . fake()->numberBetween(100, 999);
            
            $response = $this->actingAs($admin)->put(route('admin.routes.update', $route), [
                'route_name' => $newRouteName,
                'route_code' => $route->route_code,
                'description' => fake()->sentence(),
                'status' => 'active',
            ]);
            
            // Assert notification was created for each driver
            foreach ($drivers as $driver) {
                $notification = Notification::where('user_id', $driver->id)
                    ->where('type', 'route_update')
                    ->latest()
                    ->first();
                
                $this->assertNotNull($notification, "Notification should be created for driver {$driver->id}");
                $this->assertEquals('route_update', $notification->type);
                $this->assertEquals('Route Updated', $notification->title);
                $this->assertStringContainsString($route->route_code, $notification->message);
                $this->assertFalse($notification->is_read);
                $this->assertNull($notification->read_at);
                
                // Verify notification data contains route information
                $this->assertArrayHasKey('route_id', $notification->data);
                $this->assertEquals($route->id, $notification->data['route_id']);
            }
        }
    }

    /**
     * Property 54: Schedule changes create notifications
     * Validates: Requirements 10.2
     * 
     * For any schedule modification, a notification should be created for 
     * the assigned driver with type 'schedule_change'.
     */
    public function test_property_schedule_changes_create_notifications(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $admin = User::factory()->admin()->create();
            $driver = User::factory()->driver()->create();
            $route = Route::factory()->create();
            $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
            
            // Create schedule
            $schedule = Schedule::factory()->create([
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'departure_time' => '08:00',
                'day_of_week' => 'monday',
            ]);
            
            // Generate random schedule update
            $newDepartureTime = sprintf('%02d:%02d', fake()->numberBetween(6, 20), fake()->randomElement([0, 15, 30, 45]));
            $newDayOfWeek = fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            
            // Update the schedule
            $response = $this->actingAs($admin)->put(route('admin.schedules.update', $schedule), [
                'route_id' => $schedule->route_id,
                'ejeep_id' => $schedule->ejeep_id,
                'driver_id' => $schedule->driver_id,
                'departure_time' => $newDepartureTime,
                'day_of_week' => $newDayOfWeek,
                'status' => 'active',
            ]);
            
            // Assert notification was created for the driver
            $notification = Notification::where('user_id', $driver->id)
                ->where('type', 'schedule_change')
                ->latest()
                ->first();
            
            $this->assertNotNull($notification, "Notification should be created for driver {$driver->id}");
            $this->assertEquals('schedule_change', $notification->type);
            $this->assertEquals('Schedule Changed', $notification->title);
            $this->assertStringContainsString($route->route_name, $notification->message);
            $this->assertStringContainsString($newDayOfWeek, $notification->message);
            $this->assertFalse($notification->is_read);
            $this->assertNull($notification->read_at);
            
            // Verify notification data contains schedule information
            $this->assertArrayHasKey('schedule_id', $notification->data);
            $this->assertEquals($schedule->id, $notification->data['schedule_id']);
            $this->assertArrayHasKey('day_of_week', $notification->data);
            $this->assertEquals($newDayOfWeek, $notification->data['day_of_week']);
        }
    }

    /**
     * Property 55: Capacity warnings create notifications
     * Validates: Requirements 10.3
     * 
     * For any trip reaching or exceeding capacity, a notification should be 
     * created for the assigned driver with type 'capacity_warning'.
     */
    public function test_property_capacity_warnings_create_notifications(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $driver = User::factory()->driver()->create();
            $route = Route::factory()->create();
            
            // Generate random capacity
            $capacity = fake()->numberBetween(15, 30);
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            
            $trip = Trip::factory()->create([
                'driver_id' => $driver->id,
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'status' => 'in_progress',
                'current_passenger_count' => 0,
            ]);
            
            $tripService = new TripService();
            
            // Generate passenger count at or over capacity
            $passengerCount = fake()->randomElement([
                $capacity,                              // At capacity
                $capacity + fake()->numberBetween(1, 5) // Over capacity
            ]);
            
            // Record passenger count
            $tripService->recordPassengerCount($trip, $stop, $passengerCount, $passengerCount, 0);
            
            // Assert notification was created for the driver
            $notification = Notification::where('user_id', $driver->id)
                ->where('type', 'capacity_warning')
                ->latest()
                ->first();
            
            $this->assertNotNull($notification, "Capacity warning notification should be created for driver {$driver->id}");
            $this->assertEquals('capacity_warning', $notification->type);
            $this->assertEquals('Capacity Warning', $notification->title);
            $this->assertStringContainsString($route->route_name, $notification->message);
            $this->assertStringContainsString((string)$passengerCount, $notification->message);
            $this->assertStringContainsString((string)$capacity, $notification->message);
            $this->assertFalse($notification->is_read);
            $this->assertNull($notification->read_at);
            
            // Verify notification data contains trip and capacity information
            $this->assertArrayHasKey('trip_id', $notification->data);
            $this->assertEquals($trip->id, $notification->data['trip_id']);
            $this->assertArrayHasKey('current_passenger_count', $notification->data);
            $this->assertEquals($passengerCount, $notification->data['current_passenger_count']);
            $this->assertArrayHasKey('passenger_capacity', $notification->data);
            $this->assertEquals($capacity, $notification->data['passenger_capacity']);
        }
    }

    /**
     * Property 59: Critical alerts delivered immediately
     * Validates: Requirements 10.7
     * 
     * For any critical alert (capacity_warning), a notification should be 
     * created immediately when the condition is detected.
     */
    public function test_property_critical_alerts_delivered_immediately(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $driver = User::factory()->driver()->create();
            $route = Route::factory()->create();
            
            // Generate random capacity
            $capacity = fake()->numberBetween(15, 30);
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            
            $trip = Trip::factory()->create([
                'driver_id' => $driver->id,
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'status' => 'in_progress',
                'current_passenger_count' => 0,
            ]);
            
            // Count notifications before the critical event
            $notificationCountBefore = Notification::where('user_id', $driver->id)
                ->where('type', 'capacity_warning')
                ->count();
            
            $tripService = new TripService();
            
            // Trigger critical alert by exceeding capacity
            $passengerCount = $capacity + fake()->numberBetween(1, 5);
            $tripService->recordPassengerCount($trip, $stop, $passengerCount, $passengerCount, 0);
            
            // Count notifications after the critical event
            $notificationCountAfter = Notification::where('user_id', $driver->id)
                ->where('type', 'capacity_warning')
                ->count();
            
            // Assert notification was created immediately (count increased by 1)
            $this->assertEquals(
                $notificationCountBefore + 1,
                $notificationCountAfter,
                "Critical alert notification should be created immediately when condition is detected"
            );
            
            // Get the notification
            $notification = Notification::where('user_id', $driver->id)
                ->where('type', 'capacity_warning')
                ->latest()
                ->first();
            
            $this->assertNotNull($notification, "Critical alert notification should exist");
            
            // Verify it's marked as unread (ready for immediate delivery)
            $this->assertFalse($notification->is_read);
            $this->assertNull($notification->read_at);
            
            // Verify notification contains critical information
            $this->assertEquals('capacity_warning', $notification->type);
            $this->assertArrayHasKey('trip_id', $notification->data);
            $this->assertArrayHasKey('current_passenger_count', $notification->data);
            $this->assertArrayHasKey('passenger_capacity', $notification->data);
            
            // Verify the notification was created very recently (within last 5 seconds)
            $this->assertTrue(
                $notification->created_at->diffInSeconds(now()) < 5,
                "Notification should be created immediately (within 5 seconds)"
            );
        }
    }

    /**
     * Additional test: Verify no notifications for cancelled schedules
     * 
     * This ensures that only active schedules trigger notifications,
     * preventing unnecessary alerts to drivers not currently assigned.
     */
    public function test_property_no_notifications_for_cancelled_schedules(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $admin = User::factory()->admin()->create();
            $route = Route::factory()->create([
                'route_code' => 'R' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT) . substr(md5(uniqid()), 0, 6),
            ]);
            
            // Create driver with cancelled schedule
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
            
            Schedule::factory()->create([
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'status' => 'cancelled',
            ]);
            
            // Update the route
            $response = $this->actingAs($admin)->put(route('admin.routes.update', $route), [
                'route_name' => 'Updated Route ' . fake()->numberBetween(100, 999),
                'route_code' => $route->route_code,
                'description' => fake()->sentence(),
                'status' => 'active',
            ]);
            
            // Assert NO notification was created for driver with cancelled schedule
            $notificationCount = Notification::where('user_id', $driver->id)
                ->where('type', 'route_update')
                ->count();
            
            $this->assertEquals(0, $notificationCount, "No notification should be created for drivers with cancelled schedules");
        }
    }

    /**
     * Additional test: Verify no capacity warnings for under-capacity trips
     * 
     * This ensures that capacity warnings are only triggered when actually
     * at or over capacity, not for normal operation.
     */
    public function test_property_no_capacity_warnings_when_under_capacity(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $driver = User::factory()->driver()->create();
            $route = Route::factory()->create();
            
            // Generate random capacity
            $capacity = fake()->numberBetween(20, 30);
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            
            $trip = Trip::factory()->create([
                'driver_id' => $driver->id,
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'status' => 'in_progress',
                'current_passenger_count' => 0,
            ]);
            
            $tripService = new TripService();
            
            // Record passenger count UNDER capacity
            $passengerCount = fake()->numberBetween(1, $capacity - 1);
            $tripService->recordPassengerCount($trip, $stop, $passengerCount, $passengerCount, 0);
            
            // Assert NO capacity warning notification was created
            $notification = Notification::where('user_id', $driver->id)
                ->where('type', 'capacity_warning')
                ->first();
            
            $this->assertNull($notification, "No capacity warning should be created when under capacity");
        }
    }
}

