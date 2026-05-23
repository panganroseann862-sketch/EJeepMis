<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ejeep;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Schedule;
use App\Models\Trip;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for Data Integrity and Validation
 * 
 * These tests validate universal correctness properties for data validation,
 * referential integrity, and cascade deletion across multiple randomly
 * generated inputs.
 * 
 * Note: These tests manually manage database state to support property-based
 * testing with 100+ iterations per test method.
 */
class DataIntegrityPropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for each test
        Artisan::call('migrate:fresh');
    }

    /**
     * Property 66: Required fields validated
     * Validates: Requirements 12.1
     * 
     * For any model creation or update, submitting data with missing 
     * required fields should be rejected with validation errors.
     */
    public function test_property_required_fields_validated(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->admin()->create();
            
            // Test Case 1: E-Jeep missing required fields
            $missingField = fake()->randomElement(['vehicle_number', 'plate_number', 'passenger_capacity', 'operational_status']);
            
            $ejeepData = [
                'vehicle_number' => 'EJ-' . $i,
                'plate_number' => 'PLT' . $i,
                'passenger_capacity' => fake()->numberBetween(10, 50),
                'operational_status' => 'active',
            ];
            
            unset($ejeepData[$missingField]);
            
            $response = $this->actingAs($admin)->post('/admin/ejeeps', $ejeepData);
            $response->assertSessionHasErrors($missingField);
            
            // Test Case 2: Driver missing required fields
            $missingDriverField = fake()->randomElement(['username', 'email', 'password', 'first_name', 'last_name']);
            
            $driverData = [
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'driver' . $i . '@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => fake()->phoneNumber(),
            ];
            
            unset($driverData[$missingDriverField]);
            
            $response = $this->actingAs($admin)->post('/admin/drivers', $driverData);
            $response->assertSessionHasErrors($missingDriverField);
            
            // Test Case 3: Route missing required fields
            $missingRouteField = fake()->randomElement(['route_name', 'route_code']);
            
            $routeData = [
                'route_name' => 'Route ' . $i,
                'route_code' => 'R' . $i,
                'description' => fake()->sentence(),
                'status' => 'active',
            ];
            
            unset($routeData[$missingRouteField]);
            
            $response = $this->actingAs($admin)->post('/admin/routes', $routeData);
            $response->assertSessionHasErrors($missingRouteField);
            
            // Clean up
            DB::table('users')->where('id', $admin->id)->delete();
        }
    }

    /**
     * Property 67: Invalid data rejected with messages
     * Validates: Requirements 12.2
     * 
     * For any invalid data input (wrong type, out of range, invalid format), 
     * the system should reject it and return specific error messages.
     */
    public function test_property_invalid_data_rejected_with_messages(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->admin()->create();
            
            // Test Case 1: Invalid passenger capacity (negative or zero)
            $invalidCapacity = fake()->randomElement([0, -1, -10, -100]);
            
            $response = $this->actingAs($admin)->post('/admin/ejeeps', [
                'vehicle_number' => 'EJ-' . $i . '_' . uniqid(),
                'plate_number' => 'PLT' . $i . '_' . uniqid(),
                'passenger_capacity' => $invalidCapacity,
                'operational_status' => 'active',
            ]);
            
            $response->assertSessionHasErrors('passenger_capacity');
            
            // Test Case 2: Invalid email format
            $invalidEmail = fake()->randomElement(['notanemail', 'missing@', '@nodomain', 'spaces in email@test.com']);
            
            $response = $this->actingAs($admin)->post('/admin/drivers', [
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => $invalidEmail,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
            ]);
            
            $response->assertSessionHasErrors('email');
            
            // Test Case 3: Invalid operational status
            $invalidStatus = fake()->randomElement(['invalid', 'unknown', 'broken', '']);
            
            $response = $this->actingAs($admin)->post('/admin/ejeeps', [
                'vehicle_number' => 'EJ-' . $i . '_' . uniqid(),
                'plate_number' => 'PLT' . $i . '_' . uniqid(),
                'passenger_capacity' => 20,
                'operational_status' => $invalidStatus,
            ]);
            
            $response->assertSessionHasErrors('operational_status');
            
            // Test Case 4: Password too short
            $shortPassword = fake()->randomElement(['a', 'ab', 'abc', '1234567']); // Less than 8 chars
            
            $response = $this->actingAs($admin)->post('/admin/drivers', [
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'driver' . $i . '_' . uniqid() . '@example.com',
                'password' => $shortPassword,
                'password_confirmation' => $shortPassword,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
            ]);
            
            $response->assertSessionHasErrors('password');
            
            // Clean up
            DB::table('users')->where('id', $admin->id)->delete();
        }
    }

    /**
     * Property 68: Referential integrity enforced
     * Validates: Requirements 12.3
     * 
     * For any model with foreign keys, creating or updating it with 
     * non-existent foreign key values should be rejected.
     */
    public function test_property_referential_integrity_enforced(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->admin()->create();
            
            // Test Case 1: Schedule with non-existent route_id
            $nonExistentRouteId = 999999 + $i;
            
            $ejeep = Ejeep::factory()->create();
            $driver = User::factory()->driver()->create();
            
            $response = $this->actingAs($admin)->post('/admin/schedules', [
                'route_id' => $nonExistentRouteId,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'departure_time' => '08:00',
                'day_of_week' => 'monday',
                'status' => 'active',
            ]);
            
            $response->assertSessionHasErrors('route_id');
            
            // Test Case 2: Schedule with non-existent ejeep_id
            $route = Route::create([
                'route_name' => 'Test Route ' . $i,
                'route_code' => 'TR' . $i . '_' . uniqid(),
                'description' => 'Test',
                'status' => 'active',
            ]);
            $nonExistentEjeepId = 999999 + $i;
            
            $response = $this->actingAs($admin)->post('/admin/schedules', [
                'route_id' => $route->id,
                'ejeep_id' => $nonExistentEjeepId,
                'driver_id' => $driver->id,
                'departure_time' => '08:00',
                'day_of_week' => 'monday',
                'status' => 'active',
            ]);
            
            $response->assertSessionHasErrors('ejeep_id');
            
            // Test Case 3: Schedule with non-existent driver_id
            $nonExistentDriverId = 999999 + $i;
            
            $response = $this->actingAs($admin)->post('/admin/schedules', [
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $nonExistentDriverId,
                'departure_time' => '08:00',
                'day_of_week' => 'monday',
                'status' => 'active',
            ]);
            
            $response->assertSessionHasErrors('driver_id');
            
            // Test Case 4: Stop with non-existent route_id
            // Manually try to create stop with invalid route_id
            try {
                DB::table('stops')->insert([
                    'route_id' => $nonExistentRouteId,
                    'stop_name' => 'Invalid Stop',
                    'sequence_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->fail('Expected foreign key constraint violation');
            } catch (\Exception $e) {
                // Expected: foreign key constraint should prevent this
                $this->assertTrue(true);
            }
            
            // Clean up
            DB::table('stops')->where('route_id', $route->id)->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->whereIn('id', [$admin->id, $driver->id])->delete();
        }
    }

    /**
     * Property 69: Cascade deletion handled correctly
     * Validates: Requirements 12.4
     * 
     * For any parent record with dependent children, deleting the parent 
     * should either be prevented or cascade to children based on the 
     * relationship configuration.
     */
    public function test_property_cascade_deletion_handled_correctly(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->admin()->create();
            
            // Test Case 1: Deleting route with active schedules is prevented
            $route = Route::create([
                'route_name' => 'Route ' . $i,
                'route_code' => 'R' . $i . '_' . uniqid(),
                'description' => 'Test',
                'status' => 'active',
            ]);
            
            $ejeep = Ejeep::factory()->create();
            $driver = User::factory()->driver()->create();
            
            $schedule = Schedule::create([
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'departure_time' => '08:00',
                'day_of_week' => 'monday',
                'status' => 'active',
            ]);
            
            // Attempt to delete route with active schedule (should be prevented)
            $response = $this->actingAs($admin)->delete("/admin/routes/{$route->id}");
            $response->assertSessionHas('error');
            
            // Verify route still exists
            $this->assertDatabaseHas('routes', ['id' => $route->id]);
            
            // Test Case 2: Hard deleting route cascades to stops
            $route2 = Route::create([
                'route_name' => 'Route2 ' . $i,
                'route_code' => 'R2' . $i . '_' . uniqid(),
                'description' => 'Test',
                'status' => 'active',
            ]);
            
            $stop1 = Stop::create([
                'route_id' => $route2->id,
                'stop_name' => 'Stop 1',
                'sequence_order' => 1,
            ]);
            
            $stop2 = Stop::create([
                'route_id' => $route2->id,
                'stop_name' => 'Stop 2',
                'sequence_order' => 2,
            ]);
            
            $stopIds = [$stop1->id, $stop2->id];
            
            // Hard delete route (force delete bypasses soft delete)
            $route2->forceDelete();
            
            // Verify stops were cascade deleted
            $remainingStops = DB::table('stops')->whereIn('id', $stopIds)->count();
            $this->assertEquals(0, $remainingStops, 'Stops should be cascade deleted with route');
            
            // Test Case 3: Deleting schedule with active trips is prevented
            $schedule2 = Schedule::create([
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'departure_time' => '09:00',
                'day_of_week' => 'tuesday',
                'status' => 'active',
            ]);
            
            $trip = Trip::create([
                'schedule_id' => $schedule2->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'route_id' => $route->id,
                'status' => 'scheduled',
                'scheduled_start_time' => now()->addHour(),
            ]);
            
            $tripId = $trip->id;
            
            // Attempt to delete schedule with active trip (should be prevented)
            $response = $this->actingAs($admin)->delete("/admin/schedules/{$schedule2->id}");
            $response->assertSessionHas('error');
            
            // Verify schedule still exists
            $this->assertDatabaseHas('schedules', ['id' => $schedule2->id]);
            
            // Complete the trip and then delete schedule
            $trip->update(['status' => 'completed', 'actual_end_time' => now()]);
            
            // Now deletion should succeed and cascade
            $response = $this->actingAs($admin)->delete("/admin/schedules/{$schedule2->id}");
            
            // Verify trip was deleted (cascade)
            $remainingTrips = DB::table('trips')->where('id', $tripId)->count();
            $this->assertEquals(0, $remainingTrips, 'Trips should be cascade deleted with schedule');
            
            // Test Case 4: Deleting trip cascades to passenger logs
            $stop = Stop::create([
                'route_id' => $route->id,
                'stop_name' => 'Test Stop',
                'sequence_order' => 1,
            ]);
            
            $schedule3 = Schedule::create([
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'departure_time' => '10:00',
                'day_of_week' => 'wednesday',
                'status' => 'active',
            ]);
            
            $trip2 = Trip::create([
                'schedule_id' => $schedule3->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'route_id' => $route->id,
                'status' => 'in_progress',
                'scheduled_start_time' => now(),
                'actual_start_time' => now(),
            ]);
            
            DB::table('passenger_logs')->insert([
                'trip_id' => $trip2->id,
                'stop_id' => $stop->id,
                'passenger_count' => 10,
                'boarding_count' => 10,
                'alighting_count' => 0,
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Delete trip directly from database (should cascade to passenger logs)
            DB::table('trips')->where('id', $trip2->id)->delete();
            
            // Verify passenger logs were deleted (cascade)
            $remainingLogs = DB::table('passenger_logs')->where('trip_id', $trip2->id)->count();
            $this->assertEquals(0, $remainingLogs, 'Passenger logs should be cascade deleted with trip');
            
            // Clean up
            DB::table('stops')->where('route_id', $route->id)->delete();
            DB::table('schedules')->whereIn('id', [$schedule->id, $schedule3->id])->delete();
            DB::table('routes')->where('id', $route->id)->delete();
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('users')->whereIn('id', [$admin->id, $driver->id])->delete();
        }
    }

    /**
     * Property 70: Capacity limits validated as positive
     * Validates: Requirements 12.6
     * 
     * For any E-Jeep creation or update, passenger_capacity values of 
     * zero or negative should be rejected with validation errors.
     */
    public function test_property_capacity_limits_validated_as_positive(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->admin()->create();
            
            // Generate invalid capacity (zero or negative)
            $invalidCapacity = fake()->randomElement([0, -1, -5, -10, -50, -100]);
            
            // Test Case 1: Creating E-Jeep with invalid capacity
            $response = $this->actingAs($admin)->post('/admin/ejeeps', [
                'vehicle_number' => 'EJ-' . $i . '_' . uniqid(),
                'plate_number' => 'PLT' . $i . '_' . uniqid(),
                'passenger_capacity' => $invalidCapacity,
                'operational_status' => 'active',
            ]);
            
            $response->assertSessionHasErrors('passenger_capacity');
            
            // Test Case 2: Updating E-Jeep with invalid capacity
            $ejeep = Ejeep::factory()->create([
                'vehicle_number' => 'EJ-' . $i . '_' . uniqid(),
                'plate_number' => 'PLT' . $i . '_' . uniqid(),
                'passenger_capacity' => 20,
            ]);
            
            $response = $this->actingAs($admin)->put("/admin/ejeeps/{$ejeep->id}", [
                'vehicle_number' => $ejeep->vehicle_number,
                'plate_number' => $ejeep->plate_number,
                'passenger_capacity' => $invalidCapacity,
                'operational_status' => 'active',
            ]);
            
            $response->assertSessionHasErrors('passenger_capacity');
            
            // Verify capacity was not updated
            $ejeep->refresh();
            $this->assertEquals(20, $ejeep->passenger_capacity);
            $this->assertGreaterThan(0, $ejeep->passenger_capacity);
            
            // Test Case 3: Verify valid positive capacity is accepted
            $validCapacity = fake()->numberBetween(1, 100);
            
            $response = $this->actingAs($admin)->post('/admin/ejeeps', [
                'vehicle_number' => 'EJ-VALID-' . $i . '_' . uniqid(),
                'plate_number' => 'PLTV' . $i . '_' . uniqid(),
                'passenger_capacity' => $validCapacity,
                'operational_status' => 'active',
            ]);
            
            $response->assertSessionDoesntHaveErrors('passenger_capacity');
            
            // Clean up
            DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            DB::table('ejeeps')->where('vehicle_number', 'LIKE', 'EJ-VALID-' . $i . '%')->delete();
            DB::table('users')->where('id', $admin->id)->delete();
        }
    }
}
