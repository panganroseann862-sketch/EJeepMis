<?php

namespace Tests\Feature\Admin;

use App\Models\Ejeep;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas([
            'activeEjeeps',
            'driversOnTrip',
            'ongoingTrips',
            'capacityAlerts',
            'routeDeviations',
        ]);
    }

    public function test_admin_can_get_realtime_data(): void
    {
        $admin = User::factory()->admin()->create();
        
        // Create test data
        $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $schedule = Schedule::factory()->create([
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
        ]);
        
        Trip::factory()->create([
            'schedule_id' => $schedule->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'status' => 'in_progress',
            'current_passenger_count' => 15,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));

        $response->assertOk();
        $response->assertJsonStructure([
            'activeEjeeps',
            'driversOnTrip',
            'ongoingTrips',
            'capacityAlerts',
            'routeDeviations',
        ]);
    }

    public function test_realtime_data_shows_capacity_alerts(): void
    {
        $admin = User::factory()->admin()->create();
        
        // Create trip at capacity
        $ejeep = Ejeep::factory()->create([
            'operational_status' => 'active',
            'passenger_capacity' => 20,
        ]);
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $schedule = Schedule::factory()->create([
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
        ]);
        
        Trip::factory()->create([
            'schedule_id' => $schedule->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'status' => 'in_progress',
            'current_passenger_count' => 20, // At capacity
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));

        $response->assertOk();
        $response->assertJson([
            'capacityAlerts' => [
                [
                    'vehicle_number' => $ejeep->vehicle_number,
                    'current_passenger_count' => 20,
                    'passenger_capacity' => 20,
                    'is_over_capacity' => false,
                ]
            ]
        ]);
    }

    public function test_realtime_data_shows_route_deviations(): void
    {
        $admin = User::factory()->admin()->create();
        
        // Create trip with route deviation
        $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create();
        $schedule = Schedule::factory()->create([
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
        ]);
        
        Trip::factory()->create([
            'schedule_id' => $schedule->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'status' => 'in_progress',
            'has_route_deviation' => true,
            'deviation_notes' => 'Traffic detour',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));

        $response->assertOk();
        $response->assertJson([
            'routeDeviations' => [
                [
                    'vehicle_number' => $ejeep->vehicle_number,
                    'deviation_notes' => 'Traffic detour',
                    'status' => 'in_progress',
                ]
            ]
        ]);
    }

    public function test_driver_cannot_access_dashboard(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_driver_cannot_access_realtime_data(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('admin.dashboard.realtime'));

        $response->assertForbidden();
    }
}
