<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for Admin Dashboard
 * 
 * These tests validate universal correctness properties for the admin dashboard
 * across multiple randomly generated inputs to ensure dashboard metrics and
 * real-time data display correctly for all scenarios.
 * 
 * Note: These tests manually manage database state to support property-based
 * testing with 100+ iterations per test method.
 */
class AdminDashboardPropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for each test method to ensure clean state
        Artisan::call('migrate:fresh');
        
        // Reset Faker's unique generator to avoid exhaustion
        fake()->unique(true);
    }

    /**
     * Property 60: Dashboard counts active E-Jeeps
     * Validates: Requirements 11.1
     * 
     * For any set of E-Jeeps, the admin dashboard should display a count 
     * of those with operational_status = 'active'.
     */
    public function test_property_dashboard_counts_active_ejeeps(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Generate random number of E-Jeeps with various statuses
            $activeCount = fake()->numberBetween(0, 10);
            $maintenanceCount = fake()->numberBetween(0, 5);
            $inactiveCount = fake()->numberBetween(0, 5);
            
            $ejeepIds = [];
            
            // Create active E-Jeeps
            for ($j = 0; $j < $activeCount; $j++) {
                $ejeep = Ejeep::factory()->create([
                    'operational_status' => 'active',
                    'vehicle_number' => 'EJ-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLT' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
            }
            
            // Create maintenance E-Jeeps
            for ($j = 0; $j < $maintenanceCount; $j++) {
                $ejeep = Ejeep::factory()->create([
                    'operational_status' => 'maintenance',
                    'vehicle_number' => 'EJ-M-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTM' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
            }
            
            // Create inactive E-Jeeps
            for ($j = 0; $j < $inactiveCount; $j++) {
                $ejeep = Ejeep::factory()->create([
                    'operational_status' => 'inactive',
                    'vehicle_number' => 'EJ-I-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTI' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
            }
            
            // Create admin user
            $admin = User::factory()->admin()->create([
                'username' => 'admin_' . $i . '_' . uniqid(),
                'email' => 'admin' . $i . '_' . uniqid() . '@example.com',
            ]);
            
            // Get dashboard data
            $response = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));
            
            // Assert response is successful
            $response->assertOk();
            
            // Assert active E-Jeeps count matches expected
            $data = $response->json();
            $this->assertEquals($activeCount, $data['activeEjeeps'], 
                "Expected {$activeCount} active E-Jeeps, got {$data['activeEjeeps']} in iteration {$i}");
            
            // Clean up for next iteration
            DB::table('ejeeps')->whereIn('id', $ejeepIds)->delete();
            DB::table('users')->where('id', $admin->id)->delete();
        }
    }

    /**
     * Property 61: Dashboard counts drivers on trips
     * Validates: Requirements 11.2
     * 
     * For any set of drivers, the admin dashboard should display a count 
     * of those with an active trip (status 'in_progress').
     */
    public function test_property_dashboard_counts_drivers_on_trips(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Reset Faker's unique generator for each iteration
            fake()->unique(true);
            
            // Generate random number of drivers with and without active trips
            $driversOnTripCount = fake()->numberBetween(0, 8);
            $driversNotOnTripCount = fake()->numberBetween(0, 5);
            
            $userIds = [];
            $ejeepIds = [];
            $routeIds = [];
            $scheduleIds = [];
            $tripIds = [];
            
            // Create drivers with active trips
            for ($j = 0; $j < $driversOnTripCount; $j++) {
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_active_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_active' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLT' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RT-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'in_progress',
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create drivers without active trips
            for ($j = 0; $j < $driversNotOnTripCount; $j++) {
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_idle_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_idle' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
            }
            
            // Create admin user
            $admin = User::factory()->admin()->create([
                'username' => 'admin_' . $i . '_' . uniqid(),
                'email' => 'admin' . $i . '_' . uniqid() . '@example.com',
            ]);
            $userIds[] = $admin->id;
            
            // Get dashboard data
            $response = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));
            
            // Assert response is successful
            $response->assertOk();
            
            // Assert drivers on trip count matches expected
            $data = $response->json();
            $this->assertEquals($driversOnTripCount, $data['driversOnTrip'], 
                "Expected {$driversOnTripCount} drivers on trips, got {$data['driversOnTrip']} in iteration {$i}");
            
            // Clean up for next iteration
            DB::table('trips')->whereIn('id', $tripIds)->delete();
            DB::table('schedules')->whereIn('id', $scheduleIds)->delete();
            DB::table('routes')->whereIn('id', $routeIds)->delete();
            DB::table('ejeeps')->whereIn('id', $ejeepIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        }
    }

    /**
     * Property 62: Dashboard counts ongoing trips
     * Validates: Requirements 11.3
     * 
     * For any set of trips, the admin dashboard should display a count 
     * of those with status 'in_progress'.
     */
    public function test_property_dashboard_counts_ongoing_trips(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Reset Faker's unique generator for each iteration
            fake()->unique(true);
            
            // Generate random number of trips with various statuses
            $inProgressCount = fake()->numberBetween(0, 10);
            $scheduledCount = fake()->numberBetween(0, 5);
            $completedCount = fake()->numberBetween(0, 5);
            
            $userIds = [];
            $ejeepIds = [];
            $routeIds = [];
            $scheduleIds = [];
            $tripIds = [];
            
            // Create trips with in_progress status
            for ($j = 0; $j < $inProgressCount; $j++) {
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLT' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RT-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'in_progress',
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create trips with scheduled status
            for ($j = 0; $j < $scheduledCount; $j++) {
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_sched_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_sched' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-S-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTS' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RTS-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'scheduled',
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create trips with completed status
            for ($j = 0; $j < $completedCount; $j++) {
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_comp_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_comp' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-C-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTC' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RTC-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'completed',
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create admin user
            $admin = User::factory()->admin()->create([
                'username' => 'admin_' . $i . '_' . uniqid(),
                'email' => 'admin' . $i . '_' . uniqid() . '@example.com',
            ]);
            $userIds[] = $admin->id;
            
            // Get dashboard data
            $response = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));
            
            // Assert response is successful
            $response->assertOk();
            
            // Assert ongoing trips count matches expected
            $data = $response->json();
            $this->assertEquals($inProgressCount, $data['ongoingTrips'], 
                "Expected {$inProgressCount} ongoing trips, got {$data['ongoingTrips']} in iteration {$i}");
            
            // Clean up for next iteration
            DB::table('trips')->whereIn('id', $tripIds)->delete();
            DB::table('schedules')->whereIn('id', $scheduleIds)->delete();
            DB::table('routes')->whereIn('id', $routeIds)->delete();
            DB::table('ejeeps')->whereIn('id', $ejeepIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        }
    }


    /**
     * Property 63: Dashboard shows capacity alerts
     * Validates: Requirements 11.4
     * 
     * For any active trips at or over capacity, the admin dashboard 
     * should display them in the capacity alerts section.
     */
    public function test_property_dashboard_shows_capacity_alerts(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Reset Faker's unique generator for each iteration
            fake()->unique(true);
            
            // Generate random number of trips at/over capacity and under capacity
            $atCapacityCount = fake()->numberBetween(0, 5);
            $overCapacityCount = fake()->numberBetween(0, 5);
            $underCapacityCount = fake()->numberBetween(0, 5);
            
            $expectedAlertCount = $atCapacityCount + $overCapacityCount;
            
            $userIds = [];
            $ejeepIds = [];
            $routeIds = [];
            $scheduleIds = [];
            $tripIds = [];
            
            // Create trips at capacity
            for ($j = 0; $j < $atCapacityCount; $j++) {
                $capacity = fake()->numberBetween(15, 30);
                
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_at_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_at' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-AT-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTAT' . $i . $j . uniqid(),
                    'passenger_capacity' => $capacity,
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RTAT-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'in_progress',
                    'current_passenger_count' => $capacity, // At capacity
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create trips over capacity
            for ($j = 0; $j < $overCapacityCount; $j++) {
                $capacity = fake()->numberBetween(15, 30);
                $overCount = $capacity + fake()->numberBetween(1, 5);
                
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_over_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_over' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-OV-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTOV' . $i . $j . uniqid(),
                    'passenger_capacity' => $capacity,
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RTOV-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'in_progress',
                    'current_passenger_count' => $overCount, // Over capacity
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create trips under capacity
            for ($j = 0; $j < $underCapacityCount; $j++) {
                $capacity = fake()->numberBetween(15, 30);
                $underCount = fake()->numberBetween(0, $capacity - 1);
                
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_under_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_under' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-UN-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTUN' . $i . $j . uniqid(),
                    'passenger_capacity' => $capacity,
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RTUN-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'in_progress',
                    'current_passenger_count' => $underCount, // Under capacity
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create admin user
            $admin = User::factory()->admin()->create([
                'username' => 'admin_' . $i . '_' . uniqid(),
                'email' => 'admin' . $i . '_' . uniqid() . '@example.com',
            ]);
            $userIds[] = $admin->id;
            
            // Get dashboard data
            $response = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));
            
            // Assert response is successful
            $response->assertOk();
            
            // Assert capacity alerts count matches expected
            $data = $response->json();
            $actualAlertCount = count($data['capacityAlerts']);
            $this->assertEquals($expectedAlertCount, $actualAlertCount, 
                "Expected {$expectedAlertCount} capacity alerts, got {$actualAlertCount} in iteration {$i}");
            
            // Assert all alerts are at or over capacity
            foreach ($data['capacityAlerts'] as $alert) {
                $this->assertGreaterThanOrEqual($alert['passenger_capacity'], $alert['current_passenger_count'],
                    "Capacity alert should only show trips at or over capacity");
            }
            
            // Clean up for next iteration
            DB::table('trips')->whereIn('id', $tripIds)->delete();
            DB::table('schedules')->whereIn('id', $scheduleIds)->delete();
            DB::table('routes')->whereIn('id', $routeIds)->delete();
            DB::table('ejeeps')->whereIn('id', $ejeepIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        }
    }

    /**
     * Property 64: Dashboard shows route deviations
     * Validates: Requirements 11.5
     * 
     * For any trips with has_route_deviation = true, the admin dashboard 
     * should display them in the deviations section (limited to 5 most recent).
     */
    public function test_property_dashboard_shows_route_deviations(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Reset Faker's unique generator for each iteration
            fake()->unique(true);
            
            // Generate random number of trips with and without deviations
            // Note: Dashboard limits to 5 most recent deviations
            $withDeviationCount = fake()->numberBetween(0, 8);
            $withoutDeviationCount = fake()->numberBetween(0, 5);
            
            // Expected count is min of actual deviations and the limit of 5
            $expectedDeviationCount = min($withDeviationCount, 5);
            
            $userIds = [];
            $ejeepIds = [];
            $routeIds = [];
            $scheduleIds = [];
            $tripIds = [];
            
            // Create trips with route deviations
            for ($j = 0; $j < $withDeviationCount; $j++) {
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_dev_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_dev' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-DEV-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTDEV' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RTDEV-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'in_progress',
                    'has_route_deviation' => true,
                    'deviation_notes' => fake()->sentence(),
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create trips without route deviations
            for ($j = 0; $j < $withoutDeviationCount; $j++) {
                $driver = User::factory()->driver()->create([
                    'username' => 'driver_nodev_' . $i . '_' . $j . '_' . uniqid(),
                    'email' => 'driver_nodev' . $i . $j . '_' . uniqid() . '@example.com',
                ]);
                $userIds[] = $driver->id;
                
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-ND-' . $i . '-' . $j . '-' . uniqid(),
                    'plate_number' => 'PLTND' . $i . $j . uniqid(),
                ]);
                $ejeepIds[] = $ejeep->id;
                
                $route = Route::factory()->create([
                    'route_code' => 'RTND-' . $i . '-' . $j . '-' . uniqid(),
                ]);
                $routeIds[] = $route->id;
                
                $schedule = Schedule::factory()->create([
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                ]);
                $scheduleIds[] = $schedule->id;
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'status' => 'in_progress',
                    'has_route_deviation' => false,
                ]);
                $tripIds[] = $trip->id;
            }
            
            // Create admin user
            $admin = User::factory()->admin()->create([
                'username' => 'admin_' . $i . '_' . uniqid(),
                'email' => 'admin' . $i . '_' . uniqid() . '@example.com',
            ]);
            $userIds[] = $admin->id;
            
            // Get dashboard data
            $response = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));
            
            // Assert response is successful
            $response->assertOk();
            
            // Assert route deviations count matches expected (limited to 5)
            $data = $response->json();
            $actualDeviationCount = count($data['routeDeviations']);
            $this->assertEquals($expectedDeviationCount, $actualDeviationCount, 
                "Expected {$expectedDeviationCount} route deviations (limited to 5), got {$actualDeviationCount} in iteration {$i}");
            
            // Assert all deviations have vehicle number
            foreach ($data['routeDeviations'] as $deviation) {
                $this->assertNotEmpty($deviation['vehicle_number'],
                    "Route deviation should include vehicle number");
            }
            
            // Clean up for next iteration
            DB::table('trips')->whereIn('id', $tripIds)->delete();
            DB::table('schedules')->whereIn('id', $scheduleIds)->delete();
            DB::table('routes')->whereIn('id', $routeIds)->delete();
            DB::table('ejeeps')->whereIn('id', $ejeepIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        }
    }

    /**
     * Property 65: Dashboard updates reflect changes
     * Validates: Requirements 11.6
     * 
     * For any trip data change, querying the dashboard data immediately 
     * afterward should reflect the updated information.
     */
    public function test_property_dashboard_updates_reflect_changes(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Reset Faker's unique generator for each iteration
            fake()->unique(true);
            
            $userIds = [];
            $ejeepIds = [];
            $routeIds = [];
            $scheduleIds = [];
            $tripIds = [];
            
            // Create initial trip
            $driver = User::factory()->driver()->create([
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'driver' . $i . '_' . uniqid() . '@example.com',
            ]);
            $userIds[] = $driver->id;
            
            $ejeep = Ejeep::factory()->create([
                'vehicle_number' => 'EJ-' . $i . '-' . uniqid(),
                'plate_number' => 'PLT' . $i . uniqid(),
            ]);
            $ejeepIds[] = $ejeep->id;
            
            $route = Route::factory()->create([
                'route_code' => 'RT-' . $i . '-' . uniqid(),
            ]);
            $routeIds[] = $route->id;
            
            $schedule = Schedule::factory()->create([
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'route_id' => $route->id,
            ]);
            $scheduleIds[] = $schedule->id;
            
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'route_id' => $route->id,
                'status' => 'scheduled',
                'current_passenger_count' => 0,
                'has_route_deviation' => false,
            ]);
            $tripIds[] = $trip->id;
            
            // Create admin user
            $admin = User::factory()->admin()->create([
                'username' => 'admin_' . $i . '_' . uniqid(),
                'email' => 'admin' . $i . '_' . uniqid() . '@example.com',
            ]);
            $userIds[] = $admin->id;
            
            // Get initial dashboard data
            $response1 = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));
            $response1->assertOk();
            $data1 = $response1->json();
            $initialOngoingTrips = $data1['ongoingTrips'];
            
            // Update trip to in_progress
            $trip->update(['status' => 'in_progress']);
            
            // Get updated dashboard data immediately
            $response2 = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));
            $response2->assertOk();
            $data2 = $response2->json();
            
            // Assert ongoing trips count increased by 1
            $this->assertEquals($initialOngoingTrips + 1, $data2['ongoingTrips'],
                "Dashboard should reflect trip status change immediately in iteration {$i}");
            
            // Update trip to have route deviation
            $trip->update(['has_route_deviation' => true, 'deviation_notes' => 'Test deviation']);
            
            // Get updated dashboard data immediately
            $response3 = $this->actingAs($admin)->get(route('admin.dashboard.realtime'));
            $response3->assertOk();
            $data3 = $response3->json();
            
            // Assert route deviations now includes this trip
            $deviationFound = false;
            foreach ($data3['routeDeviations'] as $deviation) {
                if ($deviation['vehicle_number'] === $ejeep->vehicle_number) {
                    $deviationFound = true;
                    break;
                }
            }
            $this->assertTrue($deviationFound,
                "Dashboard should reflect route deviation immediately in iteration {$i}");
            
            // Clean up for next iteration
            DB::table('trips')->whereIn('id', $tripIds)->delete();
            DB::table('schedules')->whereIn('id', $scheduleIds)->delete();
            DB::table('routes')->whereIn('id', $routeIds)->delete();
            DB::table('ejeeps')->whereIn('id', $ejeepIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        }
    }
}
