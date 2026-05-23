<?php

namespace Tests\Feature\Driver;

use App\Models\Ejeep;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTripOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $admin;
    protected Trip $trip;
    protected Route $route;
    protected Ejeep $ejeep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = User::factory()->driver()->create();
        $this->admin = User::factory()->admin()->create();
        $this->ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $this->route = Route::factory()->create();
        
        // Create stops for the route
        for ($i = 1; $i <= 3; $i++) {
            Stop::factory()->create([
                'route_id' => $this->route->id,
                'sequence_order' => $i,
            ]);
        }

        $schedule = Schedule::factory()->create([
            'route_id' => $this->route->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
        ]);

        $this->trip = Trip::factory()->create([
            'schedule_id' => $schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_driver_can_view_trip_details(): void
    {
        $response = $this->actingAs($this->driver)
            ->get(route('driver.trips.show', $this->trip));

        $response->assertOk()
            ->assertSee($this->trip->route->route_name)
            ->assertSee($this->trip->ejeep->vehicle_number)
            ->assertSee('Trip Details');
    }

    public function test_driver_cannot_view_other_drivers_trip(): void
    {
        $otherDriver = User::factory()->driver()->create();
        
        $response = $this->actingAs($otherDriver)
            ->get(route('driver.trips.show', $this->trip));

        $response->assertForbidden();
    }

    public function test_admin_cannot_view_driver_trip_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('driver.trips.show', $this->trip));

        $response->assertForbidden();
    }

    public function test_driver_can_start_scheduled_trip(): void
    {
        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.start', $this->trip));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Trip started successfully.',
            ]);

        $this->trip->refresh();
        $this->assertEquals('in_progress', $this->trip->status);
        $this->assertNotNull($this->trip->actual_start_time);
    }

    public function test_driver_cannot_start_trip_already_in_progress(): void
    {
        $this->trip->update(['status' => 'in_progress']);

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.start', $this->trip));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_driver_can_pause_in_progress_trip(): void
    {
        $this->trip->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.pause', $this->trip));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Trip paused successfully.',
            ]);

        $this->trip->refresh();
        $this->assertEquals('paused', $this->trip->status);
    }

    public function test_driver_cannot_pause_scheduled_trip(): void
    {
        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.pause', $this->trip));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_driver_can_complete_in_progress_trip(): void
    {
        $this->trip->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.complete', $this->trip));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Trip completed successfully.',
            ]);

        $this->trip->refresh();
        $this->assertEquals('completed', $this->trip->status);
        $this->assertNotNull($this->trip->actual_end_time);
    }

    public function test_driver_cannot_complete_scheduled_trip(): void
    {
        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.complete', $this->trip));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_driver_can_record_passenger_count(): void
    {
        $this->trip->update(['status' => 'in_progress']);
        $stop = $this->route->stops->first();

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.passenger-count', $this->trip), [
                'stop_id' => $stop->id,
                'passenger_count' => 15,
                'boarding_count' => 15,
                'alighting_count' => 0,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Passenger count recorded successfully.',
            ]);

        $this->assertDatabaseHas('passenger_logs', [
            'trip_id' => $this->trip->id,
            'stop_id' => $stop->id,
            'passenger_count' => 15,
            'boarding_count' => 15,
            'alighting_count' => 0,
        ]);

        $this->trip->refresh();
        $this->assertEquals(15, $this->trip->current_passenger_count);
    }

    public function test_passenger_count_validates_required_fields(): void
    {
        $this->trip->update(['status' => 'in_progress']);

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.passenger-count', $this->trip), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stop_id', 'passenger_count', 'boarding_count', 'alighting_count']);
    }

    public function test_passenger_count_validates_non_negative_integers(): void
    {
        $this->trip->update(['status' => 'in_progress']);
        $stop = $this->route->stops->first();

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.passenger-count', $this->trip), [
                'stop_id' => $stop->id,
                'passenger_count' => -5,
                'boarding_count' => -2,
                'alighting_count' => -1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['passenger_count', 'boarding_count', 'alighting_count']);
    }

    public function test_passenger_count_validates_stop_exists(): void
    {
        $this->trip->update(['status' => 'in_progress']);

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.passenger-count', $this->trip), [
                'stop_id' => 99999,
                'passenger_count' => 10,
                'boarding_count' => 10,
                'alighting_count' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stop_id']);
    }

    public function test_passenger_count_rejects_stop_from_different_route(): void
    {
        $this->trip->update(['status' => 'in_progress']);
        $otherRoute = Route::factory()->create();
        $otherStop = Stop::factory()->create(['route_id' => $otherRoute->id]);

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.passenger-count', $this->trip), [
                'stop_id' => $otherStop->id,
                'passenger_count' => 10,
                'boarding_count' => 10,
                'alighting_count' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The selected stop does not belong to this trip\'s route.',
            ]);
    }

    public function test_over_capacity_warning_displayed(): void
    {
        $this->trip->update(['status' => 'in_progress']);
        $stop = $this->route->stops->first();

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.passenger-count', $this->trip), [
                'stop_id' => $stop->id,
                'passenger_count' => 25, // Over capacity of 20
                'boarding_count' => 25,
                'alighting_count' => 0,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_over_capacity' => true,
                    'warning' => 'Warning: Passenger count exceeds vehicle capacity!',
                ],
            ]);
    }

    public function test_remaining_capacity_calculated_correctly(): void
    {
        $this->trip->update(['status' => 'in_progress']);
        $stop = $this->route->stops->first();

        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.passenger-count', $this->trip), [
                'stop_id' => $stop->id,
                'passenger_count' => 12,
                'boarding_count' => 12,
                'alighting_count' => 0,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'current_passenger_count' => 12,
                    'remaining_capacity' => 8, // 20 - 12
                    'is_over_capacity' => false,
                ],
            ]);
    }

    public function test_driver_cannot_modify_other_drivers_trip(): void
    {
        $otherDriver = User::factory()->driver()->create();
        $this->trip->update(['status' => 'in_progress']);

        $response = $this->actingAs($otherDriver)
            ->postJson(route('driver.trips.start', $this->trip));

        $response->assertForbidden();
    }

    public function test_trip_view_displays_route_stops_in_sequence(): void
    {
        $stops = $this->route->stops()->orderBy('sequence_order')->get();

        $response = $this->actingAs($this->driver)
            ->get(route('driver.trips.show', $this->trip));

        $response->assertOk();
        
        foreach ($stops as $stop) {
            $response->assertSee($stop->stop_name);
        }
    }

    public function test_trip_view_displays_next_stop(): void
    {
        $this->trip->update(['status' => 'in_progress']);
        $firstStop = $this->route->stops()->orderBy('sequence_order')->first();

        $response = $this->actingAs($this->driver)
            ->get(route('driver.trips.show', $this->trip));

        $response->assertOk()
            ->assertSee('Next Stop')
            ->assertSee($firstStop->stop_name);
    }

    public function test_trip_view_shows_completed_stops(): void
    {
        $this->trip->update(['status' => 'in_progress']);
        $firstStop = $this->route->stops()->orderBy('sequence_order')->first();
        
        PassengerLog::factory()->create([
            'trip_id' => $this->trip->id,
            'stop_id' => $firstStop->id,
            'passenger_count' => 10,
            'boarding_count' => 10,
            'alighting_count' => 0,
        ]);

        $response = $this->actingAs($this->driver)
            ->get(route('driver.trips.show', $this->trip));

        $response->assertOk()
            ->assertSee('10 boarded');
    }

    public function test_max_passenger_count_updated(): void
    {
        $this->trip->update(['status' => 'in_progress', 'max_passenger_count' => 10]);
        $stop = $this->route->stops->first();

        $this->actingAs($this->driver)
            ->postJson(route('driver.trips.passenger-count', $this->trip), [
                'stop_id' => $stop->id,
                'passenger_count' => 18,
                'boarding_count' => 18,
                'alighting_count' => 0,
            ]);

        $this->trip->refresh();
        $this->assertEquals(18, $this->trip->max_passenger_count);
    }
}

