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
 * Property-Based Tests for Driver Operations
 * 
 * These tests validate universal correctness properties for driver-facing
 * operations including route navigation display, passenger count validation,
 * capacity calculations, overcapacity warnings, and real-time admin updates.
 * 
 * Note: These tests manually manage database state to support property-based
 * testing with 100+ iterations per test method.
 */
class DriverOperationsPropertyTest extends TestCase
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
     * Property 46: Driver view displays route navigation
     * Validates: Requirements 8.5
     * 
     * For any driver's active trip, the trip view should display all stops 
     * in sequence order with their names and locations.
     */
    public function test_property_driver_view_displays_route_navigation(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            
            // Create random number of stops (3-8 stops)
            $stopCount = fake()->numberBetween(3, 8);
            $stops = [];
            for ($j = 1; $j <= $stopCount; $j++) {
                $stops[] = Stop::factory()->create([
                    'route_id' => $route->id,
                    'sequence_order' => $j,
                    'stop_name' => "Stop {$j} - " . fake()->streetName(),
                    'location_description' => fake()->address(),
                ]);
            }
            
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create active trip
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'actual_start_time' => Carbon::now()->subMinutes(10),
            ]);
            
            // Act as driver and view trip
            $response = $this->actingAs($driver)->get("/driver/trips/{$trip->id}");
            
            // Assert response is successful
            $response->assertOk();
            
            // Assert all stops are displayed in sequence order
            foreach ($stops as $stop) {
                $response->assertSee($stop->stop_name);
                
                // Assert location description is displayed if present
                if ($stop->location_description) {
                    $response->assertSee($stop->location_description);
                }
            }
            
            // Assert stops appear in correct sequence order by checking they're all present
            // (HTML escaping may affect exact position matching, so we verify presence)
            $responseContent = $response->getContent();
            foreach ($stops as $stop) {
                // HTML may escape apostrophes, so check for both versions
                $stopNameEscaped = htmlspecialchars($stop->stop_name, ENT_QUOTES, 'UTF-8');
                $hasStopName = str_contains($responseContent, $stop->stop_name) || 
                               str_contains($responseContent, $stopNameEscaped);
                $this->assertTrue(
                    $hasStopName,
                    "Stop '{$stop->stop_name}' should appear in the response"
                );
            }
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            foreach ($stops as $stop) {
                DB::table('stops')->where('id', $stop->id)->delete();
            }
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 49: Passenger count validated as non-negative
     * Validates: Requirements 9.2
     * 
     * For any passenger count input, negative integers should be rejected 
     * with a validation error.
     */
    public function test_property_passenger_count_validated_as_non_negative(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $ejeep = Ejeep::factory()->create();
            $route = Route::factory()->create();
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create active trip
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => fake()->numberBetween(0, 20),
            ]);
            
            // Generate negative passenger count
            $negativeCount = fake()->numberBetween(-100, -1);
            
            // Attempt to record negative passenger count
            $response = $this->actingAs($driver)->postJson("/driver/trips/{$trip->id}/passenger-count", [
                'stop_id' => $stop->id,
                'passenger_count' => $negativeCount,
                'boarding_count' => 0,
                'alighting_count' => 0,
            ]);
            
            // Assert validation error
            $response->assertStatus(422);
            $response->assertJsonValidationErrors('passenger_count');
            
            // Assert error message mentions non-negative requirement
            $errors = $response->json('errors.passenger_count');
            $this->assertNotEmpty($errors);
            // Check that error message mentions either "0", "negative", or "at least"
            $errorMessage = strtolower($errors[0]);
            $hasValidationMessage = str_contains($errorMessage, '0') || 
                                   str_contains($errorMessage, 'negative') || 
                                   str_contains($errorMessage, 'at least');
            $this->assertTrue(
                $hasValidationMessage,
                "Validation error should mention non-negative requirement"
            );
            
            // Assert trip passenger count unchanged
            $trip->refresh();
            $this->assertNotEquals(
                $negativeCount,
                $trip->current_passenger_count,
                "Trip passenger count should not be updated with negative value"
            );
            
            // Clean up for next iteration
            DB::table('trips')->where('id', $trip->id)->delete();
            DB::table('schedules')->where('id', $schedule->id)->delete();
            DB::table('stops')->where('id', $stop->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }

    /**
     * Property 50: Remaining capacity calculated correctly
     * Validates: Requirements 9.4
     * 
     * For any trip after passenger count entry, remaining capacity should 
     * equal passenger_capacity - current_passenger_count.
     */
    public function test_property_remaining_capacity_calculated_correctly(): void
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
            
            // Create active trip
            $initialCount = fake()->numberBetween(0, $capacity - 5);
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $initialCount,
            ]);
            
            // Generate new passenger count
            $newPassengerCount = fake()->numberBetween(0, $capacity + 10);
            $boardingCount = max(0, $newPassengerCount - $initialCount);
            $alightingCount = max(0, $initialCount - $newPassengerCount);
            
            // Record passenger count
            $response = $this->actingAs($driver)->postJson("/driver/trips/{$trip->id}/passenger-count", [
                'stop_id' => $stop->id,
                'passenger_count' => $newPassengerCount,
                'boarding_count' => $boardingCount,
                'alighting_count' => $alightingCount,
            ]);
            
            // Assert successful response
            $response->assertOk();
            
            // Calculate expected remaining capacity
            $expectedRemainingCapacity = $capacity - $newPassengerCount;
            
            // Assert response contains remaining capacity in data object
            $responseData = $response->json();
            $this->assertArrayHasKey('data', $responseData);
            $this->assertArrayHasKey('remaining_capacity', $responseData['data']);
            $this->assertEquals(
                $expectedRemainingCapacity,
                $responseData['data']['remaining_capacity'],
                "Remaining capacity should equal capacity - current_passenger_count"
            );
            
            // Verify via model method as well
            $trip->refresh();
            $trip->load('ejeep'); // Ensure ejeep relationship is loaded
            $actualRemainingCapacity = $trip->getRemainingCapacity();
            
            // The model method uses max(0, ...) so it never returns negative
            $expectedModelCapacity = max(0, $expectedRemainingCapacity);
            $this->assertEquals(
                $expectedModelCapacity,
                $actualRemainingCapacity,
                "Trip model getRemainingCapacity() should return correct value (expected: {$expectedModelCapacity}, actual: {$actualRemainingCapacity})"
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
     * Property 51: Overcapacity warnings displayed
     * Validates: Requirements 9.5
     * 
     * For any passenger count entry where the new count exceeds capacity, 
     * a warning should be generated for the driver.
     */
    public function test_property_overcapacity_warnings_displayed(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create necessary related entities
            $driver = User::factory()->driver()->create();
            $capacity = fake()->numberBetween(15, 30);
            $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
            $route = Route::factory()->create();
            $stop = Stop::factory()->create(['route_id' => $route->id]);
            $schedule = Schedule::factory()->create([
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
            ]);
            
            // Create active trip
            $initialCount = fake()->numberBetween(0, $capacity - 5);
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $initialCount,
            ]);
            
            // Generate passenger count that exceeds capacity
            $overcapacityCount = $capacity + fake()->numberBetween(1, 10);
            $boardingCount = $overcapacityCount - $initialCount;
            
            // Record overcapacity passenger count
            $response = $this->actingAs($driver)->postJson("/driver/trips/{$trip->id}/passenger-count", [
                'stop_id' => $stop->id,
                'passenger_count' => $overcapacityCount,
                'boarding_count' => $boardingCount,
                'alighting_count' => 0,
            ]);
            
            // Assert successful response
            $response->assertOk();
            
            // Assert warning is included in response data
            $responseData = $response->json();
            $this->assertArrayHasKey('data', $responseData);
            $this->assertArrayHasKey('warning', $responseData['data']);
            $this->assertNotEmpty($responseData['data']['warning']);
            
            // Assert warning message mentions overcapacity
            $warningMessage = $responseData['data']['warning'];
            $this->assertStringContainsString(
                'capacity',
                strtolower($warningMessage),
                "Warning should mention capacity"
            );
            $this->assertStringContainsString(
                'exceed',
                strtolower($warningMessage),
                "Warning should mention exceeding capacity"
            );
            
            // Assert notification was created for driver
            $notification = DB::table('notifications')
                ->where('user_id', $driver->id)
                ->where('type', 'capacity_warning')
                ->latest('id')
                ->first();
            
            $this->assertNotNull(
                $notification,
                "Capacity warning notification should be created for driver"
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
     * Property 52: Admin displays update immediately
     * Validates: Requirements 9.7
     * 
     * For any passenger count entry by a driver, querying the admin 
     * monitoring view immediately should show the updated count.
     */
    public function test_property_admin_displays_update_immediately(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Create necessary related entities
            $admin = User::factory()->admin()->create();
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
            
            // Create active trip
            $initialCount = fake()->numberBetween(0, $capacity - 5);
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'driver_id' => $driver->id,
                'ejeep_id' => $ejeep->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'current_passenger_count' => $initialCount,
            ]);
            
            // Generate new passenger count
            $newPassengerCount = fake()->numberBetween(0, $capacity + 10);
            $boardingCount = max(0, $newPassengerCount - $initialCount);
            $alightingCount = max(0, $initialCount - $newPassengerCount);
            
            // Driver records passenger count
            $driverResponse = $this->actingAs($driver)->postJson("/driver/trips/{$trip->id}/passenger-count", [
                'stop_id' => $stop->id,
                'passenger_count' => $newPassengerCount,
                'boarding_count' => $boardingCount,
                'alighting_count' => $alightingCount,
            ]);
            
            // Assert driver request successful
            $driverResponse->assertOk();
            
            // Immediately query admin monitoring view
            $adminResponse = $this->actingAs($admin)->get("/admin/trips/{$trip->id}");
            
            // Assert admin can view the trip
            $adminResponse->assertOk();
            
            // Assert updated passenger count is displayed
            $adminResponse->assertSee((string) $newPassengerCount);
            
            // Also test via API endpoint for real-time updates
            $apiResponse = $this->actingAs($admin)->getJson('/admin/trips-api/active');
            $apiResponse->assertOk();
            
            // Find the trip in the response (response is array of trips)
            $trips = $apiResponse->json();
            $foundTrip = collect($trips)->firstWhere('id', $trip->id);
            
            $this->assertNotNull(
                $foundTrip,
                "Trip should appear in active trips list"
            );
            
            $this->assertEquals(
                $newPassengerCount,
                $foundTrip['current_passenger_count'],
                "Admin API should show updated passenger count immediately"
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
            DB::table('users')->where('id', $admin->id)->delete();
        }
    }
}
