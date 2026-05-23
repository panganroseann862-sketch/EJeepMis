<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapacityMonitoringPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected TripService $tripService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tripService = new TripService();
    }

    /**
     * Property 30: Capacity alerts generated at limit
     * Validates: Requirements 6.2
     * 
     * For any trip where current_passenger_count equals the E-Jeep's passenger_capacity,
     * a capacity alert should be generated.
     */
    public function test_property_30_capacity_alerts_generated_at_limit(): void
    {
        // Test with 20 random capacity values
        for ($i = 0; $i < 20; $i++) {
            // Generate random capacity between 10 and 50
            $capacity = rand(10, 50);
            
            // Create test data
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            $route = Route::factory()->create();
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            $trip = Trip::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => 0,
            ]);
            
            // Record passenger count exactly at capacity
            $this->tripService->recordPassengerCount($trip, $stop, $capacity, $capacity, 0);
            
            // Assert: Capacity alert notification should be generated
            $this->assertDatabaseHas('notifications', [
                'user_id' => $driver->id,
                'type' => 'capacity_warning',
            ]);
        }
    }

    /**
     * Property 31: Overcrowding flagged when exceeded
     * Validates: Requirements 6.3
     * 
     * For any trip where current_passenger_count exceeds the E-Jeep's passenger_capacity,
     * the trip should be flagged with is_over_capacity.
     */
    public function test_property_31_overcrowding_flagged_when_exceeded(): void
    {
        // Test with 20 random capacity and excess values
        for ($i = 0; $i < 20; $i++) {
            // Generate random capacity and excess
            $capacity = rand(10, 50);
            $excess = rand(1, 10);
            $passengerCount = $capacity + $excess;
            
            // Create test data
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            $route = Route::factory()->create();
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            $trip = Trip::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => 0,
            ]);
            
            // Record passenger count over capacity
            $passengerLog = $this->tripService->recordPassengerCount($trip, $stop, $passengerCount, $passengerCount, 0);
            
            // Assert: PassengerLog should be flagged as over capacity
            $this->assertTrue($passengerLog->is_over_capacity, "Passenger log should be flagged as over capacity for count {$passengerCount} with capacity {$capacity}");
            $this->assertEquals($passengerCount, $passengerLog->passenger_count);
        }
    }

    /**
     * Property 32: Dashboard displays passenger loads
     * Validates: Requirements 6.4
     * 
     * For any active trip, the admin dashboard should display its current_passenger_count.
     */
    public function test_property_32_dashboard_displays_passenger_loads(): void
    {
        // Test with 10 random passenger counts
        for ($i = 0; $i < 10; $i++) {
            // Generate random passenger count
            $capacity = rand(20, 50);
            $passengerCount = rand(5, $capacity);
            
            // Create test data
            $admin = User::factory()->admin()->create();
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            $route = Route::factory()->create();
            $schedule = Schedule::factory()->create([
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
            ]);
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $passengerCount,
                'actual_start_time' => now(),
            ]);
            
            // Act: Get admin dashboard
            $response = $this->actingAs($admin)->get('/admin/dashboard');
            
            // Assert: Dashboard should display the passenger count
            $response->assertOk();
            $response->assertSee((string)$passengerCount);
            
            // Clean up for next iteration
            $trip->delete();
            $schedule->delete();
            $route->delete();
            $ejeep->delete();
            $driver->delete();
            $admin->delete();
        }
    }

    /**
     * Property 33: Capacity alerts filter correctly
     * Validates: Requirements 6.5
     * 
     * For any set of trips, the capacity alerts view should show only those
     * where current_passenger_count >= passenger_capacity.
     */
    public function test_property_33_capacity_alerts_filter_correctly(): void
    {
        // Test with 10 different scenarios
        for ($i = 0; $i < 10; $i++) {
            $admin = User::factory()->admin()->create();
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
            $route = Route::factory()->create();
            $schedule = Schedule::factory()->create([
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
            ]);
            
            // Create trips with various passenger counts
            $underCapacity = rand(0, 19);
            $atCapacity = 20;
            $overCapacity = rand(21, 30);
            
            $tripUnder = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $underCapacity,
                'actual_start_time' => now(),
            ]);
            
            $tripAt = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $atCapacity,
                'actual_start_time' => now(),
            ]);
            
            $tripOver = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $overCapacity,
                'actual_start_time' => now(),
            ]);
            
            // Act: Get capacity alerts from dashboard
            $response = $this->actingAs($admin)->getJson('/admin/dashboard/realtime-data');
            
            // Assert: Only trips at or over capacity should be in alerts
            $response->assertOk();
            $data = $response->json();
            
            $this->assertArrayHasKey('capacityAlerts', $data);
            $this->assertIsArray($data['capacityAlerts']);
            
            // Check that at-capacity and over-capacity trips are in alerts
            $alertTripIds = collect($data['capacityAlerts'])->pluck('id')->toArray();
            $this->assertContains($tripAt->id, $alertTripIds, "Trip at capacity should be in alerts");
            $this->assertContains($tripOver->id, $alertTripIds, "Trip over capacity should be in alerts");
            $this->assertNotContains($tripUnder->id, $alertTripIds, "Trip under capacity should not be in alerts");
            
            // Clean up
            $tripUnder->delete();
            $tripAt->delete();
            $tripOver->delete();
            $schedule->delete();
            $route->delete();
            $ejeep->delete();
            $driver->delete();
            $admin->delete();
        }
    }

    /**
     * Property 34: Passenger logs track stop-by-stop changes
     * Validates: Requirements 6.6
     * 
     * For any passenger count entry at a stop, a PassengerLog record should be created
     * with the trip_id, stop_id, passenger_count, and recorded_at timestamp.
     */
    public function test_property_34_passenger_logs_track_stop_by_stop_changes(): void
    {
        // Test with 20 random passenger count entries
        for ($i = 0; $i < 20; $i++) {
            // Generate random values
            $capacity = rand(20, 50);
            $passengerCount = rand(0, $capacity + 10);
            $boardingCount = rand(0, 20);
            $alightingCount = rand(0, 20);
            
            // Create test data
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            $route = Route::factory()->create();
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            $trip = Trip::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
            ]);
            
            // Record passenger count
            $beforeCount = PassengerLog::count();
            $passengerLog = $this->tripService->recordPassengerCount(
                $trip,
                $stop,
                $passengerCount,
                $boardingCount,
                $alightingCount
            );
            
            // Assert: PassengerLog should be created with all required fields
            $this->assertEquals($beforeCount + 1, PassengerLog::count());
            $this->assertInstanceOf(PassengerLog::class, $passengerLog);
            $this->assertEquals($trip->id, $passengerLog->trip_id);
            $this->assertEquals($stop->id, $passengerLog->stop_id);
            $this->assertEquals($passengerCount, $passengerLog->passenger_count);
            $this->assertEquals($boardingCount, $passengerLog->boarding_count);
            $this->assertEquals($alightingCount, $passengerLog->alighting_count);
            $this->assertNotNull($passengerLog->recorded_at);
        }
    }

    /**
     * Property 35: Maximum passenger load recorded
     * Validates: Requirements 6.7
     * 
     * For any completed trip, max_passenger_count should equal the highest
     * current_passenger_count value recorded during the trip.
     */
    public function test_property_35_maximum_passenger_load_recorded(): void
    {
        // Test with 10 trips with varying passenger counts
        for ($i = 0; $i < 10; $i++) {
            // Generate random capacity and passenger counts
            $capacity = rand(20, 50);
            $numStops = rand(3, 6);
            
            // Create test data
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            $route = Route::factory()->create();
            $trip = Trip::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => 0,
                'max_passenger_count' => 0,
            ]);
            
            // Record passenger counts at multiple stops
            $passengerCounts = [];
            for ($j = 0; $j < $numStops; $j++) {
                $stop = Stop::factory()->create([
                    'route_id' => $route->id,
                    'sequence_order' => $j + 1,
                ]);
                
                $count = rand(5, $capacity + 5);
                $passengerCounts[] = $count;
                
                $this->tripService->recordPassengerCount($trip, $stop, $count, rand(0, 10), rand(0, 10));
            }
            
            // Get the maximum count from all recorded counts
            $expectedMax = max($passengerCounts);
            
            // Assert: Trip's max_passenger_count should equal the highest recorded value
            $trip->refresh();
            $this->assertEquals($expectedMax, $trip->max_passenger_count, "Max passenger count should be {$expectedMax} but got {$trip->max_passenger_count}");
        }
    }
}
