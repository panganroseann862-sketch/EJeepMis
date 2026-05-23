<?php

namespace Tests\Feature\Driver;

use App\Models\Ejeep;
use App\Models\Notification;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_view_dashboard(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get('/driver/dashboard');

        $response->assertOk()
            ->assertViewIs('driver.dashboard')
            ->assertViewHas(['driver', 'todaySchedules', 'currentTrip', 'upcomingTrips', 'unreadNotificationsCount']);
    }

    public function test_admin_cannot_access_driver_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/driver/dashboard');

        $response->assertForbidden();
    }

    public function test_dashboard_displays_current_day_schedules(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create();
        
        $currentDay = strtolower(now()->format('l'));
        
        // Create schedule for today
        $todaySchedule = Schedule::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'day_of_week' => $currentDay,
            'status' => 'active',
        ]);
        
        // Create schedule for different day
        $differentDay = $currentDay === 'monday' ? 'tuesday' : 'monday';
        Schedule::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'day_of_week' => $differentDay,
            'status' => 'active',
        ]);

        $response = $this->actingAs($driver)->get('/driver/dashboard');

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('todaySchedules')->count());
        $this->assertEquals($todaySchedule->id, $response->viewData('todaySchedules')->first()->id);
    }

    public function test_dashboard_displays_current_active_trip(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create();
        $schedule = Schedule::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
        ]);
        
        // Create active trip
        $activeTrip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'schedule_id' => $schedule->id,
            'status' => 'in_progress',
        ]);
        
        // Create completed trip
        Trip::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'schedule_id' => $schedule->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($driver)->get('/driver/dashboard');

        $response->assertOk();
        $this->assertNotNull($response->viewData('currentTrip'));
        $this->assertEquals($activeTrip->id, $response->viewData('currentTrip')->id);
    }

    public function test_dashboard_displays_upcoming_trips(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create();
        $schedule = Schedule::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
        ]);
        
        // Create upcoming trip for today
        $upcomingTrip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'schedule_id' => $schedule->id,
            'status' => 'scheduled',
            'scheduled_start_time' => now()->addHours(2),
        ]);
        
        // Create trip for tomorrow
        Trip::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'schedule_id' => $schedule->id,
            'status' => 'scheduled',
            'scheduled_start_time' => now()->addDay(),
        ]);

        $response = $this->actingAs($driver)->get('/driver/dashboard');

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('upcomingTrips')->count());
        $this->assertEquals($upcomingTrip->id, $response->viewData('upcomingTrips')->first()->id);
    }

    public function test_dashboard_displays_unread_notifications_count(): void
    {
        $driver = User::factory()->driver()->create();
        
        // Create unread notifications
        Notification::factory()->count(3)->create([
            'user_id' => $driver->id,
            'is_read' => false,
        ]);
        
        // Create read notification
        Notification::factory()->create([
            'user_id' => $driver->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($driver)->get('/driver/dashboard');

        $response->assertOk();
        $this->assertEquals(3, $response->viewData('unreadNotificationsCount'));
    }

    public function test_get_assigned_trips_api_returns_json(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create();
        
        $currentDay = strtolower(now()->format('l'));
        
        Schedule::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'day_of_week' => $currentDay,
            'status' => 'active',
        ]);

        $response = $this->actingAs($driver)->get('/driver/dashboard/assigned-trips');

        $response->assertOk()
            ->assertJsonStructure([
                'todaySchedules',
                'currentTrip',
                'upcomingTrips',
                'unreadNotificationsCount',
            ]);
    }

    public function test_dashboard_shows_no_current_trip_when_driver_not_on_trip(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get('/driver/dashboard');

        $response->assertOk();
        $this->assertNull($response->viewData('currentTrip'));
    }

    public function test_dashboard_only_shows_active_schedules(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create();
        
        $currentDay = strtolower(now()->format('l'));
        
        // Create active schedule
        Schedule::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'day_of_week' => $currentDay,
            'status' => 'active',
        ]);
        
        // Create cancelled schedule
        Schedule::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'day_of_week' => $currentDay,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($driver)->get('/driver/dashboard');

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('todaySchedules')->count());
    }
}
