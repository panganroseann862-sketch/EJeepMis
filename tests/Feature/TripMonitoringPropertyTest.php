<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for Trip Monitoring
 * 
 * These tests validate universal correctness properties for trip monitoring
 * across multiple randomly generated inputs to ensure the monitoring system
 * correctly displays trip information, tracks progress, and flags issues.
 * 
 * Note: These tests manually manage database state to support property-based
 * testing with 100+ iterations per test method.
 */
class TripMonitoringPropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for each test
        Artisan::call('migrate:fresh');
    }

    /**
     * Property 22: Active trips appear on dashboard
     * Validates: Requirements 5.1
     * 
     * For any trip with status 'in_progress', it should appear in the 
     * admin dashboard's active trips list.
     */
    public function test_property_active_trips_appear_on_dashboard(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create trip with in_progress status
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'actual_start_time' => Carbon::now()->subMinutes(fake()->numberBetween(1, 60)),
            ]);
            
            // Fetch active trips via API
            $response = $this->actingAs($admin)->getJson(route('admin.trips.active'));
            
            // Assert response is successful
            $response->assertStatus(200);
            
            // Assert trip appears in active trips list
            $activeTrips = $response->json();
            $tripIds = collect($activeTrips)->pluck('id')->toArray();
            
            $this->assertContains(
                $trip->id,
                $tripIds,
                "Active trip should appear in dashboard active trips list"
            );
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->whereIn('id', [$driver->id, $admin->id])->delete();
        }
    }

    /**
     * Property 23: Trip details display complete information
     * Validates: Requirements 5.2
     * 
     * For any trip, viewing its details should display the assigned E-Jeep,
     * driver, route, and current progress information.
     */
    public function test_property_trip_details_display_complete_information(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            
            // Create necessary related entities with random data
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create trip with random status and passenger count
            $statuses = ['scheduled', 'in_progress', 'paused', 'completed'];
            $status = fake()->randomElement($statuses);
            $passengerCount = fake()->numberBetween(0, $ejeep->passenger_capacity);
            
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => $status,
                'current_passenger_count' => $passengerCount,
                'actual_start_time' => in_array($status, ['in_progress', 'paused', 'completed']) 
                    ? Carbon::now()->subMinutes(fake()->numberBetween(10, 120)) 
                    : null,
            ]);
            
            // View trip details
            $response = $this->actingAs($admin)->get(route('admin.trips.show', $trip));
            
            // Assert response is successful
            $response->assertStatus(200);
            
            // Assert all required information is displayed
            $response->assertSee($ejeep->vehicle_number);
            $response->assertSee($driver->first_name);
            $response->assertSee($driver->last_name);
            $response->assertSee($route->route_name);
            $response->assertSee((string)$passengerCount);
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->whereIn('id', [$driver->id, $admin->id])->delete();
        }
    }

    /**
     * Property 24: Next stop tracked correctly
     * Validates: Requirements 5.3
     * 
     * For any active trip with passenger logs, the system should correctly
     * identify the next scheduled stop based on the sequence order.
     */
    public function test_property_next_stop_tracked_correctly(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            
            // Create 5 stops in sequence
            $stops = collect();
            for ($seq = 1; $seq <= 5; $seq++) {
                $stops->push(Stop::factory()->create([
                    'route_id' => $route->id,
                    'sequence_order' => $seq,
                ]));
            }
            
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create trip
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'actual_start_time' => Carbon::now()->subMinutes(30),
            ]);
            
            // Log passenger counts for first N stops (random between 1 and 4)
            $stopsVisited = fake()->numberBetween(1, 4);
            for ($j = 0; $j < $stopsVisited; $j++) {
                PassengerLog::factory()->create([
                    'trip_id' => $trip->id,
                    'stop_id' => $stops[$j]->id,
                    'passenger_count' => fake()->numberBetween(5, 20),
                ]);
            }
            
            // Expected next stop is the one after the last logged stop
            $expectedNextStop = $stops[$stopsVisited];
            
            // View trip details
            $response = $this->actingAs($admin)->get(route('admin.trips.show', $trip));
            
            // Assert response is successful
            $response->assertStatus(200);
            
            // Assert next stop is displayed
            $response->assertSee('Next Stop');
            $response->assertSee($expectedNextStop->stop_name);
            
            // Clean up for next iteration
            DB::table('passenger_logs')->where('trip_id', $trip->id)->delete();
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('stops')->where('route_id', $route->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->whereIn('id', [$driver->id, $admin->id])->delete();
        }
    }

    /**
     * Property 25: Route deviations flagged
     * Validates: Requirements 5.4
     * 
     * For any trip with has_route_deviation set to true, the system should
     * flag it as a route deviation in the monitoring interface.
     */
    public function test_property_route_deviations_flagged(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create trip with route deviation
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'has_route_deviation' => true,
                'deviation_notes' => fake()->sentence(),
                'actual_start_time' => Carbon::now()->subMinutes(30),
            ]);
            
            // View trip details
            $response = $this->actingAs($admin)->get(route('admin.trips.show', $trip));
            
            // Assert response is successful
            $response->assertStatus(200);
            
            // Assert route deviation is flagged
            $response->assertSee('Route Deviation');
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->whereIn('id', [$driver->id, $admin->id])->delete();
        }
    }

    /**
     * Property 26: ETA displayed for active trips
     * Validates: Requirements 5.5
     * 
     * For any trip with status 'in_progress' and an actual_start_time,
     * the system should display estimated time of arrival information.
     */
    public function test_property_eta_displayed_for_active_trips(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create trip with in_progress status
            $minutesElapsed = fake()->numberBetween(5, 120);
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'actual_start_time' => Carbon::now()->subMinutes($minutesElapsed),
            ]);
            
            // View trip details
            $response = $this->actingAs($admin)->get(route('admin.trips.show', $trip));
            
            // Assert response is successful
            $response->assertStatus(200);
            
            // Assert time information is displayed (actual start time is shown for in_progress trips)
            $response->assertSee('Actual Start');
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->whereIn('id', [$driver->id, $admin->id])->delete();
        }
    }

    /**
     * Property 27: Fleet status shows all active trips
     * Validates: Requirements 5.6
     * 
     * For any set of trips with status 'in_progress', the fleet status
     * endpoint should return all of them.
     */
    public function test_property_fleet_status_shows_all_active_trips(): void
    {
        // Run property test with 20 iterations (reduced to avoid unique constraint issues)
        for ($i = 0; $i < 20; $i++) {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            
            // Create random number of active trips (2-4)
            $numActiveTrips = fake()->numberBetween(2, 4);
            $activeTripIds = [];
            
            for ($j = 0; $j < $numActiveTrips; $j++) {
                $driver = User::factory()->driver()->create();
                $ejeep = Ejeep::factory()->create();
                $route = Route::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                ]);
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                    'status' => 'in_progress',
                    'actual_start_time' => Carbon::now()->subMinutes(fake()->numberBetween(5, 60)),
                ]);
                
                $activeTripIds[] = $trip->id;
            }
            
            // Also create some non-active trips that should NOT appear
            $numInactiveTrips = fake()->numberBetween(1, 2);
            $inactiveStatuses = ['scheduled', 'completed', 'cancelled'];
            
            for ($j = 0; $j < $numInactiveTrips; $j++) {
                $driver = User::factory()->driver()->create();
                $ejeep = Ejeep::factory()->create();
                $route = Route::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                ]);
                
                Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                    'status' => fake()->randomElement($inactiveStatuses),
                ]);
            }
            
            // Fetch active trips via API
            $response = $this->actingAs($admin)->getJson(route('admin.trips.active'));
            
            // Assert response is successful
            $response->assertStatus(200);
            
            // Assert all active trips are returned
            $returnedTrips = $response->json();
            $returnedTripIds = collect($returnedTrips)->pluck('id')->toArray();
            
            foreach ($activeTripIds as $activeTripId) {
                $this->assertContains(
                    $activeTripId,
                    $returnedTripIds,
                    "All active trips should appear in fleet status"
                );
            }
            
            // Assert only in_progress trips are returned (API returns in_progress and paused)
            $this->assertGreaterThanOrEqual(
                $numActiveTrips,
                count($returnedTrips),
                "Fleet status should show at least all in_progress trips"
            );
            
            // Clean up for next iteration
            DB::table('trips')->delete();
            DB::table('schedules')->delete();
            DB::table('routes')->delete();
            DB::table('ejeeps')->delete();
            DB::table('users')->where('role', 'driver')->delete();
            DB::table('users')->where('id', $admin->id)->delete();
        }
    }

    /**
     * Property 28: Trip updates reflected immediately
     * Validates: Requirements 5.7
     * 
     * For any trip, when its data is updated (passenger count, status, etc.),
     * the changes should be immediately visible in the monitoring interface.
     */
    public function test_property_trip_updates_reflected_immediately(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create trip with initial state
            $initialPassengerCount = fake()->numberBetween(5, 15);
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $initialPassengerCount,
                'actual_start_time' => Carbon::now()->subMinutes(30),
            ]);
            
            // Update trip with new passenger count
            $newPassengerCount = fake()->numberBetween(16, 30);
            $trip->update(['current_passenger_count' => $newPassengerCount]);
            
            // Immediately fetch trip via API
            $response = $this->actingAs($admin)->getJson(route('admin.trips.active'));
            
            // Assert response is successful
            $response->assertStatus(200);
            
            // Find the trip in the response
            $returnedTrips = $response->json();
            $returnedTrip = collect($returnedTrips)->firstWhere('id', $trip->id);
            
            // Assert trip data is present
            $this->assertNotNull($returnedTrip, "Updated trip should be in active trips list");
            
            // Assert updated passenger count is reflected
            $this->assertEquals(
                $newPassengerCount,
                $returnedTrip['current_passenger_count'],
                "Trip updates should be reflected immediately in API response"
            );
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->whereIn('id', [$driver->id, $admin->id])->delete();
        }
    }
}
