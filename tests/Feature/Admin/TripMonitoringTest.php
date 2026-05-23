<?php

namespace Tests\Feature\Admin;

use App\Models\Ejeep;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $driver;
    private Ejeep $ejeep;
    private Route $route;
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->driver = User::factory()->create(['role' => 'driver']);
        $this->ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $this->route = Route::factory()->create();
        
        // Create stops for the route with proper sequence ordering
        for ($i = 1; $i <= 3; $i++) {
            Stop::factory()->create([
                'route_id' => $this->route->id,
                'sequence_order' => $i,
            ]);
        }

        $this->schedule = Schedule::factory()->create([
            'route_id' => $this->route->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
        ]);
    }

    public function test_admin_can_view_trips_index(): void
    {
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.trips.index'));

        $response->assertStatus(200);
        $response->assertSee($this->ejeep->vehicle_number);
        $response->assertSee($this->driver->first_name);
        $response->assertSee($this->route->route_name);
    }

    public function test_admin_can_filter_trips_by_status(): void
    {
        Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
        ]);

        Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.trips.index', ['status' => 'in_progress']));

        $response->assertStatus(200);
        $response->assertSee('In Progress');
    }

    public function test_admin_can_view_trip_details(): void
    {
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
            'current_passenger_count' => 15,
        ]);

        $stops = $this->route->stops;
        PassengerLog::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stops->first()->id,
            'passenger_count' => 15,
            'boarding_count' => 15,
            'alighting_count' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.trips.show', $trip));

        $response->assertStatus(200);
        $response->assertSee($this->ejeep->vehicle_number);
        $response->assertSee($this->driver->first_name);
        $response->assertSee($this->route->route_name);
        $response->assertSee('15');
        $response->assertSee($stops->first()->stop_name);
    }

    public function test_trip_details_show_next_stop(): void
    {
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
        ]);

        $stops = $this->route->stops()->orderBy('sequence_order')->get();
        
        // Log first stop
        PassengerLog::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stops->first()->id,
            'passenger_count' => 10,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.trips.show', $trip));

        $response->assertStatus(200);
        $response->assertSee('Next Stop');
        $response->assertSee($stops->get(1)->stop_name);
    }

    public function test_trip_details_show_over_capacity_warning(): void
    {
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'current_passenger_count' => 25, // Over capacity of 20
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.trips.show', $trip));

        $response->assertStatus(200);
        $response->assertSee('Over Capacity');
    }

    public function test_get_active_trips_api_returns_json(): void
    {
        Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
            'current_passenger_count' => 15,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.trips.active'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'ejeep',
                'driver',
                'route',
                'status',
                'current_passenger_count',
                'capacity',
                'is_over_capacity',
                'has_route_deviation',
            ]
        ]);
    }

    public function test_get_capacity_alerts_api_returns_over_capacity_trips(): void
    {
        // Create over capacity trip
        Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
            'current_passenger_count' => 25, // Over capacity of 20
        ]);

        // Create normal capacity trip
        Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
            'current_passenger_count' => 10,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.trips.capacity-alerts'));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['current_passenger_count' => 25]);
    }

    public function test_driver_cannot_access_trip_monitoring(): void
    {
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
        ]);

        $response = $this->actingAs($this->driver)->get(route('admin.trips.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->driver)->get(route('admin.trips.show', $trip));
        $response->assertStatus(403);
    }

    public function test_trip_details_display_passenger_logs(): void
    {
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'in_progress',
        ]);

        $stops = $this->route->stops()->orderBy('sequence_order')->get();
        
        PassengerLog::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stops->first()->id,
            'passenger_count' => 10,
            'boarding_count' => 10,
            'alighting_count' => 0,
        ]);

        PassengerLog::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stops->get(1)->id,
            'passenger_count' => 15,
            'boarding_count' => 8,
            'alighting_count' => 3,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.trips.show', $trip));

        $response->assertStatus(200);
        $response->assertSee('Passenger Logs');
        $response->assertSee('10'); // boarding count
        $response->assertSee('8'); // boarding count
        $response->assertSee('3'); // alighting count
    }
}
