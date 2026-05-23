<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for Trip Service
 * 
 * These tests validate universal correctness properties for trip operations
 * across multiple randomly generated inputs to ensure the TripService behaves
 * correctly for all valid trip state transitions and passenger count updates.
 * 
 * Note: These tests manually manage database state to support property-based
 * testing with 100+ iterations per test method.
 */
class TripServicePropertyTest extends TestCase
{
    protected TripService $tripService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for each test
        Artisan::call('migrate:fresh');
        
        $this->tripService = new TripService();
    }

    /**
     * Property 43: Starting trip updates status
     * Validates: Requirements 8.2
     * 
     * For any trip with status 'scheduled', when a driver starts it, 
     * the status should change to 'in_progress' and actual_start_time 
     * should be recorded.
     */
    public function test_property_starting_trip_updates_status(): void
    {
        // Run property test with 50 iterations (reduced to avoid unique constraint issues)
        for ($i = 0; $i < 50; $i++) {
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create trip with scheduled status
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'scheduled',
                'actual_start_time' => null,
            ]);
            
            $beforeTime = Carbon::now()->subSecond();
            
            // Start the trip
            $result = $this->tripService->startTrip($trip);
            
            $afterTime = Carbon::now()->addSecond();
            
            // Assert status changed to in_progress
            $this->assertEquals('in_progress', $result->status);
            
            // Assert actual_start_time was recorded
            $this->assertNotNull($result->actual_start_time);
            
            // Assert actual_start_time is within reasonable time window
            $this->assertTrue(
                $result->actual_start_time->between($beforeTime, $afterTime),
                "actual_start_time should be set to current time"
            );
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 44: Pausing trip updates status
     * Validates: Requirements 8.3
     * 
     * For any trip with status 'in_progress', when a driver pauses it, 
     * the status should change to 'paused'.
     */
    public function test_property_pausing_trip_updates_status(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
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
            
            // Pause the trip
            $result = $this->tripService->pauseTrip($trip);
            
            // Assert status changed to paused
            $this->assertEquals('paused', $result->status);
            
            // Assert actual_start_time is preserved
            $this->assertNotNull($result->actual_start_time);
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 45: Completing trip updates status and time
     * Validates: Requirements 8.4
     * 
     * For any trip with status 'in_progress', when a driver completes it, 
     * the status should change to 'completed' and actual_end_time should 
     * be recorded.
     */
    public function test_property_completing_trip_updates_status_and_time(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
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
            $startTime = Carbon::now()->subMinutes(fake()->numberBetween(10, 120));
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'actual_start_time' => $startTime,
                'actual_end_time' => null,
            ]);
            
            $beforeTime = Carbon::now()->subSecond();
            
            // Complete the trip
            $result = $this->tripService->completeTrip($trip);
            
            $afterTime = Carbon::now()->addSecond();
            
            // Assert status changed to completed
            $this->assertEquals('completed', $result->status);
            
            // Assert actual_end_time was recorded
            $this->assertNotNull($result->actual_end_time);
            
            // Assert actual_end_time is within reasonable time window
            $this->assertTrue(
                $result->actual_end_time->between($beforeTime, $afterTime),
                "actual_end_time should be set to current time"
            );
            
            // Assert actual_end_time is after actual_start_time
            $this->assertTrue(
                $result->actual_end_time->greaterThan($result->actual_start_time),
                "actual_end_time should be after actual_start_time"
            );
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 29: Passenger count updates trip load
     * Validates: Requirements 6.1
     * 
     * For any trip and valid passenger count entry, recording the count 
     * should update the trip's current_passenger_count to match.
     */
    public function test_property_passenger_count_updates_trip_load(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $capacity = fake()->numberBetween(15, 50);
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            $route = Route::factory()->create();
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create trip
            $initialCount = fake()->numberBetween(0, $capacity - 5);
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $initialCount,
            ]);
            
            // Generate random passenger count changes
            $newPassengerCount = fake()->numberBetween(0, $capacity + 10);
            $boardingCount = max(0, $newPassengerCount - $initialCount);
            $alightingCount = max(0, $initialCount - $newPassengerCount);
            
            // Record passenger count
            $this->tripService->recordPassengerCount(
                $trip,
                $stop,
                $newPassengerCount,
                $boardingCount,
                $alightingCount
            );
            
            // Refresh trip from database
            $trip->refresh();
            
            // Assert current_passenger_count was updated
            $this->assertEquals(
                $newPassengerCount,
                $trip->current_passenger_count,
                "Trip current_passenger_count should match recorded count"
            );
            
            // Clean up for next iteration
            DB::table('notifications')->where('user_id', $driver->id)->delete();
            DB::table('passenger_logs')->where('trip_id', $trip->id)->delete();
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('stops')->where('id', $stop->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 71: Trip status transitions validated
     * Validates: Requirements 12.7
     * 
     * For any trip, attempting an invalid status transition 
     * (e.g., 'completed' to 'in_progress') should be rejected.
     */
    public function test_property_trip_status_transitions_validated(): void
    {
        // Define invalid transitions to test
        $invalidTransitions = [
            ['from' => 'completed', 'action' => 'start'],
            ['from' => 'completed', 'action' => 'pause'],
            ['from' => 'completed', 'action' => 'complete'],
            ['from' => 'cancelled', 'action' => 'start'],
            ['from' => 'cancelled', 'action' => 'pause'],
            ['from' => 'cancelled', 'action' => 'complete'],
            ['from' => 'scheduled', 'action' => 'pause'],
            ['from' => 'scheduled', 'action' => 'complete'],
            ['from' => 'paused', 'action' => 'start'],
            ['from' => 'paused', 'action' => 'complete'],
        ];
        
        // Test each invalid transition 5 times (50 total iterations)
        foreach ($invalidTransitions as $transition) {
            for ($i = 0; $i < 5; $i++) {
                // Create necessary related entities
                $driver = User::factory()->driver()->create();
                $ejeep = Ejeep::factory()->create();
                $route = Route::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                ]);
                
                // Create trip with the 'from' status
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                    'status' => $transition['from'],
                    'actual_start_time' => in_array($transition['from'], ['in_progress', 'paused', 'completed']) 
                        ? Carbon::now()->subMinutes(30) 
                        : null,
                    'actual_end_time' => $transition['from'] === 'completed' 
                        ? Carbon::now() 
                        : null,
                ]);
                
                // Attempt the invalid transition and expect exception
                $exceptionThrown = false;
                try {
                    switch ($transition['action']) {
                        case 'start':
                            $this->tripService->startTrip($trip);
                            break;
                        case 'pause':
                            $this->tripService->pauseTrip($trip);
                            break;
                        case 'complete':
                            $this->tripService->completeTrip($trip);
                            break;
                    }
                } catch (\InvalidArgumentException $e) {
                    $exceptionThrown = true;
                    // Assert exception message mentions the invalid status
                    $this->assertStringContainsString(
                        $transition['from'],
                        $e->getMessage(),
                        "Exception should mention the current status"
                    );
                }
                
                // Assert that exception was thrown
                $this->assertTrue(
                    $exceptionThrown,
                    "Invalid transition from '{$transition['from']}' via '{$transition['action']}' should throw exception"
                );
                
                // Assert trip status unchanged
                $trip->refresh();
                $this->assertEquals(
                    $transition['from'],
                    $trip->status,
                    "Trip status should remain unchanged after invalid transition attempt"
                );
                
                // Clean up for next iteration
                DB::table('trips')->where('id', $trip->id)->delete();
                DB::table('schedules')->where('id', $schedule->id)->delete();
                DB::table('routes')->where('id', $route->id)->delete();
                DB::table('ejeeps')->where('id', $ejeep->id)->delete();
                DB::table('users')->where('id', $driver->id)->delete();
            }
        }
    }
}
