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

class NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_update_notifies_assigned_drivers(): void
    {
        $admin = User::factory()->admin()->create();
        $driver1 = User::factory()->driver()->create();
        $driver2 = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create();

        // Create schedules for both drivers on this route
        Schedule::factory()->create([
            'route_id' => $route->id,
            'driver_id' => $driver1->id,
            'ejeep_id' => $ejeep->id,
            'status' => 'active',
        ]);
        Schedule::factory()->create([
            'route_id' => $route->id,
            'driver_id' => $driver2->id,
            'ejeep_id' => $ejeep->id,
            'status' => 'active',
        ]);

        // Update the route
        $this->actingAs($admin)->put(route('admin.routes.update', $route), [
            'route_name' => 'Updated Route Name',
            'route_code' => $route->route_code,
            'status' => 'active',
        ]);

        // Both drivers should receive notifications
        $this->assertEquals(1, Notification::where('user_id', $driver1->id)->where('type', 'route_update')->count());
        $this->assertEquals(1, Notification::where('user_id', $driver2->id)->where('type', 'route_update')->count());
    }

    public function test_schedule_update_notifies_driver(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create();
        $schedule = Schedule::factory()->create([
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'ejeep_id' => $ejeep->id,
        ]);

        // Update the schedule
        $this->actingAs($admin)->put(route('admin.schedules.update', $schedule), [
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'departure_time' => '09:00',
            'day_of_week' => 'tuesday',
            'status' => 'active',
        ]);

        // Driver should receive notification
        $notification = Notification::where('user_id', $driver->id)
            ->where('type', 'schedule_change')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('Schedule Changed', $notification->title);
        $this->assertFalse($notification->is_read);
    }

    public function test_capacity_warning_notifies_driver_when_at_capacity(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'status' => 'in_progress',
            'current_passenger_count' => 0,
        ]);

        $tripService = new TripService();

        // Record passenger count at capacity
        $tripService->recordPassengerCount($trip, $stop, 20, 20, 0);

        // Driver should receive capacity warning
        $notification = Notification::where('user_id', $driver->id)
            ->where('type', 'capacity_warning')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('Capacity Warning', $notification->title);
        $this->assertStringContainsString('20', $notification->message);
    }

    public function test_capacity_warning_notifies_driver_when_over_capacity(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'status' => 'in_progress',
            'current_passenger_count' => 0,
        ]);

        $tripService = new TripService();

        // Record passenger count over capacity
        $tripService->recordPassengerCount($trip, $stop, 25, 25, 0);

        // Driver should receive capacity warning
        $notification = Notification::where('user_id', $driver->id)
            ->where('type', 'capacity_warning')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('Capacity Warning', $notification->title);
        $this->assertStringContainsString('25', $notification->message);
        $this->assertStringContainsString('exceeded', $notification->message);
    }

    public function test_no_capacity_warning_when_under_capacity(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'status' => 'in_progress',
            'current_passenger_count' => 0,
        ]);

        $tripService = new TripService();

        // Record passenger count under capacity
        $tripService->recordPassengerCount($trip, $stop, 15, 15, 0);

        // Driver should NOT receive capacity warning
        $notification = Notification::where('user_id', $driver->id)
            ->where('type', 'capacity_warning')
            ->first();

        $this->assertNull($notification);
    }

    public function test_route_update_only_notifies_active_schedule_drivers(): void
    {
        $admin = User::factory()->admin()->create();
        $activeDriver = User::factory()->driver()->create();
        $cancelledDriver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create();

        // Create active schedule
        Schedule::factory()->create([
            'route_id' => $route->id,
            'driver_id' => $activeDriver->id,
            'ejeep_id' => $ejeep->id,
            'status' => 'active',
        ]);

        // Create cancelled schedule
        Schedule::factory()->create([
            'route_id' => $route->id,
            'driver_id' => $cancelledDriver->id,
            'ejeep_id' => $ejeep->id,
            'status' => 'cancelled',
        ]);

        // Update the route
        $this->actingAs($admin)->put(route('admin.routes.update', $route), [
            'route_name' => 'Updated Route',
            'route_code' => $route->route_code,
            'status' => 'active',
        ]);

        // Only active driver should receive notification
        $this->assertEquals(1, Notification::where('user_id', $activeDriver->id)->count());
        $this->assertEquals(0, Notification::where('user_id', $cancelledDriver->id)->count());
    }
}
