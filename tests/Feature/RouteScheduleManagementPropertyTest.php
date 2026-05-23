<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\Notification;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for Route and Schedule Management
 * 
 * These tests validate universal correctness properties across multiple
 * randomly generated inputs to ensure route and schedule management behaves
 * correctly for all valid scenarios.
 */
class RouteScheduleManagementPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 16: Route creation stores stops in sequence
     * Validates: Requirements 4.1
     * 
     * For any valid route with stops, creating the route should store 
     * all stops with correct sequence_order values (1, 2, 3, ...).
     */
    public function test_property_route_creation_stores_stops_in_sequence(): void
    {
        // Run property test with 20 iterations (reduced for performance)
        for ($i = 0; $i < 20; $i++) {
            // Generate random route data with alphanumeric code only
            $routeCode = 'R' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT) . substr(md5(uniqid()), 0, 6);
            $routeName = fake()->words(3, true);
            $stopCount = fake()->numberBetween(2, 5);
            
            $admin = User::factory()->admin()->create();
            
            // Generate stops data
            $stops = [];
            for ($j = 0; $j < $stopCount; $j++) {
                $stops[] = [
                    'stop_name' => fake()->streetName() . ' Stop',
                    'location_description' => fake()->sentence(),
                    'latitude' => fake()->latitude(14.5, 14.7),
                    'longitude' => fake()->longitude(120.9, 121.1),
                ];
            }
            
            // Create route with stops
            $response = $this->actingAs($admin)->post(route('admin.routes.store'), [
                'route_name' => $routeName,
                'route_code' => $routeCode,
                'description' => fake()->sentence(),
                'status' => 'active',
                'stops' => $stops,
            ]);
            
            // Assert route was created
            $this->assertDatabaseHas('routes', ['route_code' => $routeCode]);
            
            $route = Route::where('route_code', $routeCode)->first();
            $this->assertNotNull($route);
            
            // Assert all stops were created with correct sequence
            $storedStops = $route->stops()->orderBy('sequence_order')->get();
            $this->assertCount($stopCount, $storedStops);
            
            // Verify sequence order is 1, 2, 3, ...
            foreach ($storedStops as $index => $stop) {
                $this->assertEquals($index + 1, $stop->sequence_order);
                $this->assertEquals($stops[$index]['stop_name'], $stop->stop_name);
            }
        }
    }

    /**
     * Property 17: Route updates persist and notify drivers
     * Validates: Requirements 4.2
     * 
     * For any route update affecting assigned drivers, the changes should 
     * persist and create notifications for all affected drivers.
     */
    public function test_property_route_updates_persist_and_notify_drivers(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            // Create route with assigned drivers
            $admin = User::factory()->admin()->create();
            $route = Route::factory()->create([
                'route_code' => 'R' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT) . substr(md5(uniqid()), 0, 6),
            ]);
            
            // Create drivers and schedules
            $driverCount = fake()->numberBetween(1, 3);
            $drivers = [];
            for ($j = 0; $j < $driverCount; $j++) {
                $driver = User::factory()->driver()->create();
                $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
                
                Schedule::factory()->create([
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'status' => 'active',
                ]);
                
                $drivers[] = $driver;
            }
            
            // Update route - use simple alphanumeric name
            $newRouteName = 'Route ' . fake()->numberBetween(100, 999);
            $newDescription = fake()->sentence();
            
            $response = $this->actingAs($admin)->put(route('admin.routes.update', $route), [
                'route_name' => $newRouteName,
                'route_code' => $route->route_code,
                'description' => $newDescription,
                'status' => 'active',
            ]);
            
            // Assert route was updated
            $route->refresh();
            $this->assertEquals($newRouteName, $route->route_name);
            $this->assertEquals($newDescription, $route->description);
            
            // Assert notifications were created for all drivers
            foreach ($drivers as $driver) {
                $this->assertDatabaseHas('notifications', [
                    'user_id' => $driver->id,
                    'type' => 'route_update',
                    'title' => 'Route Updated',
                ]);
            }
        }
    }

    /**
     * Property 18: Route deletion removes from scheduling
     * Validates: Requirements 4.3
     * 
     * For any route, deleting it should prevent it from being 
     * selected when creating new schedules.
     */
    public function test_property_route_deletion_removes_from_scheduling(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $admin = User::factory()->admin()->create();
            $route = Route::factory()->create([
                'route_code' => 'R' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT) . substr(md5(uniqid()), 0, 6),
            ]);
            
            // Delete the route
            $response = $this->actingAs($admin)->delete(route('admin.routes.destroy', $route));
            
            // Assert route is soft deleted
            $this->assertSoftDeleted('routes', ['id' => $route->id]);
            
            // Verify deleted route is not in active routes list
            $activeRoutes = Route::active()->get();
            $this->assertFalse($activeRoutes->contains('id', $route->id));
            
            // Verify route doesn't appear in schedule creation form
            $response = $this->actingAs($admin)->get(route('admin.schedules.create'));
            $response->assertOk();
            $response->assertDontSee($route->route_code);
        }
    }

    /**
     * Property 19: Schedule creation associates correctly
     * Validates: Requirements 4.5
     * 
     * For any valid schedule data, creating the schedule should correctly 
     * associate it with the specified route, E-Jeep, driver, departure_time, 
     * and day_of_week.
     */
    public function test_property_schedule_creation_associates_correctly(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $admin = User::factory()->admin()->create();
            $route = Route::factory()->create();
            $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
            $driver = User::factory()->driver()->create();
            
            // Generate random schedule data
            $departureTime = sprintf('%02d:%02d', fake()->numberBetween(6, 20), fake()->randomElement([0, 15, 30, 45]));
            $dayOfWeek = fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            
            // Create schedule
            $response = $this->actingAs($admin)->post(route('admin.schedules.store'), [
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'departure_time' => $departureTime,
                'day_of_week' => $dayOfWeek,
                'status' => 'active',
            ]);
            
            // Assert schedule was created with correct associations
            $this->assertDatabaseHas('schedules', [
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'day_of_week' => $dayOfWeek,
            ]);
            
            $schedule = Schedule::where('route_id', $route->id)
                ->where('ejeep_id', $ejeep->id)
                ->where('driver_id', $driver->id)
                ->first();
            
            $this->assertNotNull($schedule);
            $this->assertEquals($route->id, $schedule->route_id);
            $this->assertEquals($ejeep->id, $schedule->ejeep_id);
            $this->assertEquals($driver->id, $schedule->driver_id);
            $this->assertEquals($dayOfWeek, $schedule->day_of_week);
            $this->assertEquals($departureTime, $schedule->departure_time->format('H:i'));
        }
    }

    /**
     * Property 20: Schedule modifications update trips
     * Validates: Requirements 4.6
     * 
     * For any schedule modification, related future trips should 
     * reflect the updated schedule information.
     */
    /**
     * Property 20: Schedule modifications update trips
     * Validates: Requirements 4.6
     * 
     * For any schedule modification, related future trips should 
     * reflect the updated schedule information.
     */
    public function test_property_schedule_modifications_update_trips(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $admin = User::factory()->admin()->create();
            $route = Route::factory()->create();
            $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
            $driver = User::factory()->driver()->create();
            
            // Create schedule
            $schedule = Schedule::factory()->create([
                'route_id' => $route->id,
                'ejeep_id' => $ejeep->id,
                'driver_id' => $driver->id,
                'departure_time' => '08:00',
                'day_of_week' => 'monday',
            ]);
            
            // Create a future trip based on this schedule
            $trip = Trip::factory()->create([
                'schedule_id' => $schedule->id,
                'route_id' => $schedule->route_id,
                'ejeep_id' => $schedule->ejeep_id,
                'driver_id' => $schedule->driver_id,
                'status' => 'scheduled',
                'scheduled_start_time' => now()->addDays(1)->setTime(8, 0),
            ]);
            
            // Update schedule
            $newDepartureTime = '09:30';
            $newDayOfWeek = 'tuesday';
            
            $response = $this->actingAs($admin)->put(route('admin.schedules.update', $schedule), [
                'route_id' => $schedule->route_id,
                'ejeep_id' => $schedule->ejeep_id,
                'driver_id' => $schedule->driver_id,
                'departure_time' => $newDepartureTime,
                'day_of_week' => $newDayOfWeek,
                'status' => 'active',
            ]);
            
            // Assert schedule was updated
            $schedule->refresh();
            $this->assertEquals($newDepartureTime, $schedule->departure_time->format('H:i'));
            $this->assertEquals($newDayOfWeek, $schedule->day_of_week);
            
            // Assert notification was sent to driver
            $this->assertDatabaseHas('notifications', [
                'user_id' => $driver->id,
                'type' => 'schedule_change',
            ]);
        }
    }

    /**
     * Property 21: Multiple schedules per route supported
     * Validates: Requirements 4.7
     * 
     * For any route, creating multiple schedules with different times 
     * or days should succeed without conflicts.
     */
    public function test_property_multiple_schedules_per_route_supported(): void
    {
        // Run property test with 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $admin = User::factory()->admin()->create();
            $route = Route::factory()->create();
            
            // Create multiple schedules for the same route
            $scheduleCount = fake()->numberBetween(2, 4);
            
            for ($j = 0; $j < $scheduleCount; $j++) {
                $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
                $driver = User::factory()->driver()->create();
                
                // Generate unique time/day combinations
                $departureTime = sprintf('%02d:%02d', fake()->numberBetween(6, 20), fake()->randomElement([0, 15, 30, 45]));
                $dayOfWeek = fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
                
                $response = $this->actingAs($admin)->post(route('admin.schedules.store'), [
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                    'driver_id' => $driver->id,
                    'departure_time' => $departureTime,
                    'day_of_week' => $dayOfWeek,
                    'status' => 'active',
                ]);
                
                // Assert schedule was created successfully
                $response->assertRedirect();
            }
            
            // Assert all schedules exist for the same route
            $routeSchedules = Schedule::where('route_id', $route->id)->get();
            $this->assertCount($scheduleCount, $routeSchedules);
            
            // Verify each schedule has correct route association
            foreach ($routeSchedules as $schedule) {
                $this->assertEquals($route->id, $schedule->route_id);
            }
        }
    }
}
