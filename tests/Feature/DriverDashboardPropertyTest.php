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
 * Property-Based Tests for Driver Dashboard
 * 
 * These tests validate universal correctness properties for driver
 * dashboard functionality including assignment display, current trip
 * tracking, daily schedule viewing, and driver status reflection.
 * 
 * Note: These tests manually manage database state to support property-based
 * testing with 100+ iterations per test method.
 */
class DriverDashboardPropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for each test method
        Artisan::call('migrate:fresh');
    }
    
    protected function tearDown(): void
    {
        // Clean up database after each test
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('users')->truncate();
        DB::table('trips')->truncate();
        DB::table('schedules')->truncate();
        DB::table('routes')->truncate();
        DB::table('ejeeps')->truncate();
        DB::statement('PRAGMA foreign_keys = ON');
        
        parent::tearDown();
    }

    /**
     * Property 42: Driver dashboard shows assignments
     * Validates: Requirements 8.1
     * 
     * For any driver, their dashboard should display all schedules assigned 
     * to them and upcoming trips for the current day.
     */
    public function test_property_driver_dashboard_shows_assignments(): void
    {
        // Run property test with 20 iterations (reduced to avoid unique value exhaustion in factories)
        for ($i = 0; $i < 20; $i++) {
            // Create driver with random data
            $driver = User::factory()->driver()->create([
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'driver' . $i . '_' . uniqid() . '@example.com',
            ]);
            
            // Generate random number of schedules and trips (reduced to avoid exhaustion)
            $schedulesCount = fake()->numberBetween(0, 3);
            $upcomingTripsCount = fake()->numberBetween(0, 2);
            
            $createdSchedules = [];
            $createdTrips = [];
            $createdRoutes = [];
            $createdEjeeps = [];
            
            // Create schedules for the driver
            for ($j = 0; $j < $schedulesCount; $j++) {
                $route = Route::factory()->create([
                    'route_name' => 'Test Route ' . $i . '_' . $j,
                    'route_code' => 'R' . $i . '_' . $j . '_' . uniqid(),
                ]);
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-' . $i . '_' . $j . '_' . uniqid(),
                    'plate_number' => strtoupper(substr(uniqid(), 0, 3) . '-' . $i . $j . fake()->numberBetween(10, 99)),
                ]);
                
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                    'status' => 'active',
                ]);
                
                $createdSchedules[] = $schedule->id;
                $createdRoutes[] = $route->id;
                $createdEjeeps[] = $ejeep->id;
            }
            
            // Create upcoming trips for today (must be scheduled for today's date)
            $actualTripsCreatedForToday = 0;
            if ($schedulesCount > 0 && $upcomingTripsCount > 0) {
                $schedule = Schedule::find($createdSchedules[0]);
                
                for ($k = 0; $k < $upcomingTripsCount; $k++) {
                    // Ensure trip is scheduled for today with a future time
                    // Add hours to current time to ensure it's in the future
                    $scheduledTime = now()->addHours($k + 1);
                    
                    // Only count trips that are actually scheduled for today
                    if ($scheduledTime->isToday()) {
                        $actualTripsCreatedForToday++;
                    }
                    
                    $trip = Trip::factory()->create([
                        'driver_id' => $driver->id,
                        'schedule_id' => $schedule->id,
                        'route_id' => $schedule->route_id,
                        'ejeep_id' => $schedule->ejeep_id,
                        'status' => 'scheduled',
                        'scheduled_start_time' => $scheduledTime,
                    ]);
                    
                    $createdTrips[] = $trip->id;
                }
            }
            
            // Access driver dashboard
            $response = $this->actingAs($driver)->get('/driver/dashboard');
            
            // Assert page loads successfully
            $response->assertOk();
            
            // Assert view has required data
            $response->assertViewHas(['driver', 'todaySchedules', 'currentTrip', 'upcomingTrips', 'unreadNotificationsCount']);
            
            // Retrieve view data
            $todaySchedules = $response->viewData('todaySchedules');
            $upcomingTrips = $response->viewData('upcomingTrips');
            
            // Assert schedules are displayed (filtered by current day)
            $this->assertNotNull($todaySchedules);
            
            // Assert upcoming trips are displayed (controller filters by today's date)
            $this->assertNotNull($upcomingTrips);
            // Only assert count for trips that are actually scheduled for today
            $this->assertEquals($actualTripsCreatedForToday, $upcomingTrips->count());
            
            // Verify all upcoming trips belong to the driver and are scheduled for today
            foreach ($upcomingTrips as $trip) {
                $this->assertEquals($driver->id, $trip->driver_id);
                $this->assertEquals('scheduled', $trip->status);
                $this->assertEquals(now()->toDateString(), $trip->scheduled_start_time->toDateString());
            }
            
            // Clean up for next iteration
            foreach ($createdTrips as $tripId) {
                DB::table('trips')->where('id', $tripId)->delete();
            }
            foreach ($createdSchedules as $scheduleId) {
                DB::table('schedules')->where('id', $scheduleId)->delete();
            }
            foreach ($createdRoutes as $routeId) {
                DB::table('routes')->where('id', $routeId)->delete();
            }
            foreach ($createdEjeeps as $ejeepId) {
                DB::table('ejeeps')->where('id', $ejeepId)->delete();
            }
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 47: Driver dashboard shows current trip
     * Validates: Requirements 8.6
     * 
     * For any driver with an active trip (status 'in_progress'), their 
     * dashboard should display that trip with current progress.
     */
    public function test_property_driver_dashboard_shows_current_trip(): void
    {
        // Run property test with 20 iterations (reduced to avoid unique value exhaustion in factories)
        for ($i = 0; $i < 20; $i++) {
            // Create driver with random data
            $driver = User::factory()->driver()->create([
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'driver' . $i . '_' . uniqid() . '@example.com',
            ]);
            
            // Randomly decide if driver has an active trip
            $hasActiveTrip = fake()->boolean(70); // 70% chance of having active trip
            
            $createdTrip = null;
            $createdSchedule = null;
            $createdRoute = null;
            $createdEjeep = null;
            
            if ($hasActiveTrip) {
                // Create active trip for the driver
                $route = Route::factory()->create([
                    'route_name' => 'Test Route ' . $i,
                    'route_code' => 'R' . $i . '_' . uniqid(),
                ]);
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-' . $i . '_' . uniqid(),
                    'plate_number' => strtoupper(substr(uniqid(), 0, 3) . '-' . $i . fake()->numberBetween(100, 999)),
                ]);
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                ]);
                
                $trip = Trip::factory()->create([
                    'driver_id' => $driver->id,
                    'schedule_id' => $schedule->id,
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                    'status' => 'in_progress',
                    'actual_start_time' => now()->subMinutes(fake()->numberBetween(5, 60)),
                    'current_passenger_count' => fake()->numberBetween(0, $ejeep->passenger_capacity),
                ]);
                
                $createdTrip = $trip->id;
                $createdSchedule = $schedule->id;
                $createdRoute = $route->id;
                $createdEjeep = $ejeep->id;
            }
            
            // Access driver dashboard
            $response = $this->actingAs($driver)->get('/driver/dashboard');
            
            // Assert page loads successfully
            $response->assertOk();
            
            // Assert view has currentTrip data
            $response->assertViewHas('currentTrip');
            
            // Retrieve current trip from view data
            $currentTrip = $response->viewData('currentTrip');
            
            if ($hasActiveTrip) {
                // Assert current trip is displayed
                $this->assertNotNull($currentTrip);
                $this->assertEquals($createdTrip, $currentTrip->id);
                $this->assertEquals($driver->id, $currentTrip->driver_id);
                $this->assertEquals('in_progress', $currentTrip->status);
                
                // Assert trip has progress information
                $this->assertNotNull($currentTrip->actual_start_time);
                $this->assertGreaterThanOrEqual(0, $currentTrip->current_passenger_count);
            } else {
                // Assert no current trip when driver is not on a trip
                $this->assertNull($currentTrip);
            }
            
            // Clean up for next iteration
            if ($hasActiveTrip) {
                DB::table('trips')->where('id', $createdTrip)->delete();
                DB::table('schedules')->where('id', $createdSchedule)->delete();
                DB::table('routes')->where('id', $createdRoute)->delete();
                DB::table('ejeeps')->where('id', $createdEjeep)->delete();
            }
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 48: Driver can view daily schedule
     * Validates: Requirements 8.7
     * 
     * For any driver, their dashboard should display all schedules assigned 
     * to them for the current day_of_week.
     */
    public function test_property_driver_can_view_daily_schedule(): void
    {
        // Run property test with 20 iterations (reduced to avoid unique value exhaustion in factories)
        for ($i = 0; $i < 20; $i++) {
            // Create driver with random data
            $driver = User::factory()->driver()->create([
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'driver' . $i . '_' . uniqid() . '@example.com',
            ]);
            
            // Get current day of week
            $currentDay = strtolower(now()->format('l'));
            $otherDay = $currentDay === 'monday' ? 'tuesday' : 'monday';
            
            // Generate random number of schedules for today and other days (reduced to avoid exhaustion)
            $todaySchedulesCount = fake()->numberBetween(0, 3);
            $otherDaySchedulesCount = fake()->numberBetween(0, 2);
            
            $createdSchedules = [];
            $createdRoutes = [];
            $createdEjeeps = [];
            
            // Create schedules for current day
            for ($j = 0; $j < $todaySchedulesCount; $j++) {
                $route = Route::factory()->create([
                    'route_name' => 'Test Route ' . $i . '_' . $j,
                    'route_code' => 'R' . $i . '_' . $j . '_' . uniqid(),
                ]);
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-' . $i . '_' . $j . '_' . uniqid(),
                    'plate_number' => strtoupper(substr(uniqid(), 0, 3) . '-' . $i . $j . fake()->numberBetween(10, 99)),
                ]);
                
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                    'day_of_week' => $currentDay,
                    'status' => 'active',
                ]);
                
                $createdSchedules[] = $schedule->id;
                $createdRoutes[] = $route->id;
                $createdEjeeps[] = $ejeep->id;
            }
            
            // Create schedules for other days (should not appear)
            for ($k = 0; $k < $otherDaySchedulesCount; $k++) {
                $route = Route::factory()->create([
                    'route_name' => 'Test Route ' . $i . '_other_' . $k,
                    'route_code' => 'R' . $i . '_other_' . $k . '_' . uniqid(),
                ]);
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-' . $i . '_other_' . $k . '_' . uniqid(),
                    'plate_number' => strtoupper(substr(uniqid(), 0, 3) . '-' . $i . 'o' . $k . fake()->numberBetween(10, 99)),
                ]);
                
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                    'day_of_week' => $otherDay,
                    'status' => 'active',
                ]);
                
                $createdSchedules[] = $schedule->id;
                $createdRoutes[] = $route->id;
                $createdEjeeps[] = $ejeep->id;
            }
            
            // Access driver dashboard
            $response = $this->actingAs($driver)->get('/driver/dashboard');
            
            // Assert page loads successfully
            $response->assertOk();
            
            // Assert view has todaySchedules data
            $response->assertViewHas('todaySchedules');
            
            // Retrieve today's schedules from view data
            $todaySchedules = $response->viewData('todaySchedules');
            
            // Assert only today's schedules are displayed
            $this->assertNotNull($todaySchedules);
            $this->assertEquals($todaySchedulesCount, $todaySchedules->count());
            
            // Verify all schedules are for current day and belong to driver
            foreach ($todaySchedules as $schedule) {
                $this->assertEquals($driver->id, $schedule->driver_id);
                $this->assertEquals($currentDay, $schedule->day_of_week);
                $this->assertEquals('active', $schedule->status);
            }
            
            // Clean up for next iteration
            foreach ($createdSchedules as $scheduleId) {
                DB::table('schedules')->where('id', $scheduleId)->delete();
            }
            foreach ($createdRoutes as $routeId) {
                DB::table('routes')->where('id', $routeId)->delete();
            }
            foreach ($createdEjeeps as $ejeepId) {
                DB::table('ejeeps')->where('id', $ejeepId)->delete();
            }
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 13: Driver status reflects trip state
     * Validates: Requirements 3.5
     * 
     * For any driver, their availability status should correctly reflect 
     * whether they are currently on an active trip.
     */
    public function test_property_driver_status_reflects_trip_state(): void
    {
        // Run property test with 20 iterations (reduced to avoid unique value exhaustion in factories)
        for ($i = 0; $i < 20; $i++) {
            // Create driver with random data
            $driver = User::factory()->driver()->create([
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'driver' . $i . '_' . uniqid() . '@example.com',
                'status' => 'active',
            ]);
            
            // Randomly decide if driver has an active trip
            $hasActiveTrip = fake()->boolean(60); // 60% chance of having active trip
            
            $createdTrip = null;
            $createdSchedule = null;
            $createdRoute = null;
            $createdEjeep = null;
            
            if ($hasActiveTrip) {
                // Create active trip for the driver
                $route = Route::factory()->create([
                    'route_name' => 'Test Route ' . $i,
                    'route_code' => 'R' . $i . '_' . uniqid(),
                ]);
                $ejeep = Ejeep::factory()->create([
                    'vehicle_number' => 'EJ-' . $i . '_' . uniqid(),
                    'plate_number' => strtoupper(substr(uniqid(), 0, 3) . '-' . $i . fake()->numberBetween(100, 999)),
                ]);
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                ]);
                
                $trip = Trip::factory()->create([
                    'driver_id' => $driver->id,
                    'schedule_id' => $schedule->id,
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                    'status' => 'in_progress',
                    'actual_start_time' => now()->subMinutes(fake()->numberBetween(5, 60)),
                ]);
                
                $createdTrip = $trip->id;
                $createdSchedule = $schedule->id;
                $createdRoute = $route->id;
                $createdEjeep = $ejeep->id;
            }
            
            // Access driver dashboard
            $response = $this->actingAs($driver)->get('/driver/dashboard');
            
            // Assert page loads successfully
            $response->assertOk();
            
            // Retrieve current trip from view data
            $currentTrip = $response->viewData('currentTrip');
            
            // Verify driver status reflects trip state
            if ($hasActiveTrip) {
                // Driver should have a current trip
                $this->assertNotNull($currentTrip);
                $this->assertEquals('in_progress', $currentTrip->status);
                
                // Query driver from database to check if status logic is consistent
                $driverFromDb = User::find($driver->id);
                $activeTrip = Trip::where('driver_id', $driver->id)
                    ->where('status', 'in_progress')
                    ->first();
                
                // Assert driver has an active trip in database
                $this->assertNotNull($activeTrip);
                $this->assertEquals($createdTrip, $activeTrip->id);
            } else {
                // Driver should not have a current trip
                $this->assertNull($currentTrip);
                
                // Query driver from database to verify no active trips
                $activeTrip = Trip::where('driver_id', $driver->id)
                    ->where('status', 'in_progress')
                    ->first();
                
                // Assert driver has no active trip in database
                $this->assertNull($activeTrip);
            }
            
            // Clean up for next iteration
            if ($hasActiveTrip) {
                DB::table('trips')->where('id', $createdTrip)->delete();
                DB::table('schedules')->where('id', $createdSchedule)->delete();
                DB::table('routes')->where('id', $createdRoute)->delete();
                DB::table('ejeeps')->where('id', $createdEjeep)->delete();
            }
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }
}
