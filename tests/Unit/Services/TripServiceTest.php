<?php

namespace Tests\Unit\Services;

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

class TripServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TripService $tripService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tripService = new TripService();
    }

    public function test_start_trip_transitions_from_scheduled_to_in_progress(): void
    {
        $trip = Trip::factory()->create(['status' => 'scheduled']);

        $result = $this->tripService->startTrip($trip);

        $this->assertEquals('in_progress', $result->status);
        $this->assertNotNull($result->actual_start_time);
    }

    public function test_start_trip_throws_exception_for_non_scheduled_trip(): void
    {
        $trip = Trip::factory()->create(['status' => 'in_progress']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot start trip with status 'in_progress'");

        $this->tripService->startTrip($trip);
    }

    public function test_pause_trip_transitions_from_in_progress_to_paused(): void
    {
        $trip = Trip::factory()->create(['status' => 'in_progress']);

        $result = $this->tripService->pauseTrip($trip);

        $this->assertEquals('paused', $result->status);
    }

    public function test_pause_trip_throws_exception_for_non_in_progress_trip(): void
    {
        $trip = Trip::factory()->create(['status' => 'scheduled']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot pause trip with status 'scheduled'");

        $this->tripService->pauseTrip($trip);
    }

    public function test_complete_trip_transitions_from_in_progress_to_completed(): void
    {
        $trip = Trip::factory()->create(['status' => 'in_progress']);

        $result = $this->tripService->completeTrip($trip);

        $this->assertEquals('completed', $result->status);
        $this->assertNotNull($result->actual_end_time);
    }

    public function test_complete_trip_throws_exception_for_non_in_progress_trip(): void
    {
        $trip = Trip::factory()->create(['status' => 'scheduled']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot complete trip with status 'scheduled'");

        $this->tripService->completeTrip($trip);
    }

    public function test_record_passenger_count_creates_passenger_log(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
            'current_passenger_count' => 0,
            'max_passenger_count' => 0,
        ]);

        $result = $this->tripService->recordPassengerCount($trip, $stop, 15, 15, 0);

        $this->assertInstanceOf(PassengerLog::class, $result);
        $this->assertEquals($trip->id, $result->trip_id);
        $this->assertEquals($stop->id, $result->stop_id);
        $this->assertEquals(15, $result->passenger_count);
        $this->assertEquals(15, $result->boarding_count);
        $this->assertEquals(0, $result->alighting_count);
        $this->assertFalse($result->is_over_capacity);
    }

    public function test_record_passenger_count_updates_trip_current_count(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
            'current_passenger_count' => 10,
        ]);

        $this->tripService->recordPassengerCount($trip, $stop, 18, 8, 0);

        $trip->refresh();
        $this->assertEquals(18, $trip->current_passenger_count);
    }

    public function test_record_passenger_count_updates_max_passenger_count(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
            'current_passenger_count' => 10,
            'max_passenger_count' => 10,
        ]);

        $this->tripService->recordPassengerCount($trip, $stop, 18, 8, 0);

        $trip->refresh();
        $this->assertEquals(18, $trip->max_passenger_count);
    }

    public function test_record_passenger_count_flags_over_capacity(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
        ]);

        $result = $this->tripService->recordPassengerCount($trip, $stop, 25, 25, 0);

        $this->assertTrue($result->is_over_capacity);
    }

    public function test_check_capacity_returns_true_when_at_capacity(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'current_passenger_count' => 20,
        ]);

        $result = $this->tripService->checkCapacity($trip);

        $this->assertTrue($result);
    }

    public function test_check_capacity_returns_true_when_over_capacity(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'current_passenger_count' => 25,
        ]);

        $result = $this->tripService->checkCapacity($trip);

        $this->assertTrue($result);
    }

    public function test_check_capacity_returns_false_when_under_capacity(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'current_passenger_count' => 15,
        ]);

        $result = $this->tripService->checkCapacity($trip);

        $this->assertFalse($result);
    }

    public function test_detect_route_deviation_returns_current_status(): void
    {
        $trip = Trip::factory()->create(['has_route_deviation' => true]);

        $result = $this->tripService->detectRouteDeviation($trip);

        $this->assertTrue($result);
    }

    // Edge Case Tests

    /**
     * Test invalid status transitions are rejected
     */
    public function test_start_trip_rejects_completed_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'completed']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot start trip with status 'completed'");

        $this->tripService->startTrip($trip);
    }

    public function test_start_trip_rejects_cancelled_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'cancelled']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot start trip with status 'cancelled'");

        $this->tripService->startTrip($trip);
    }

    public function test_start_trip_rejects_paused_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'paused']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot start trip with status 'paused'");

        $this->tripService->startTrip($trip);
    }

    public function test_pause_trip_rejects_completed_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'completed']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot pause trip with status 'completed'");

        $this->tripService->pauseTrip($trip);
    }

    public function test_pause_trip_rejects_cancelled_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'cancelled']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot pause trip with status 'cancelled'");

        $this->tripService->pauseTrip($trip);
    }

    public function test_pause_trip_rejects_paused_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'paused']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot pause trip with status 'paused'");

        $this->tripService->pauseTrip($trip);
    }

    public function test_complete_trip_rejects_completed_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'completed']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot complete trip with status 'completed'");

        $this->tripService->completeTrip($trip);
    }

    public function test_complete_trip_rejects_cancelled_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'cancelled']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot complete trip with status 'cancelled'");

        $this->tripService->completeTrip($trip);
    }

    public function test_complete_trip_rejects_paused_status(): void
    {
        $trip = Trip::factory()->create(['status' => 'paused']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot complete trip with status 'paused'");

        $this->tripService->completeTrip($trip);
    }

    /**
     * Test passenger count validation (negative values)
     */
    public function test_record_passenger_count_handles_negative_passenger_count(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
            'current_passenger_count' => 0,
        ]);

        // The service should handle negative values gracefully
        // In a real system, validation would happen at the request level
        $result = $this->tripService->recordPassengerCount($trip, $stop, -5, 0, 0);

        $this->assertInstanceOf(PassengerLog::class, $result);
        $this->assertEquals(-5, $result->passenger_count);
        
        $trip->refresh();
        $this->assertEquals(-5, $trip->current_passenger_count);
    }

    public function test_record_passenger_count_handles_negative_boarding_count(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
        ]);

        $result = $this->tripService->recordPassengerCount($trip, $stop, 10, -5, 0);

        $this->assertInstanceOf(PassengerLog::class, $result);
        $this->assertEquals(-5, $result->boarding_count);
    }

    public function test_record_passenger_count_handles_negative_alighting_count(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
        ]);

        $result = $this->tripService->recordPassengerCount($trip, $stop, 10, 0, -3);

        $this->assertInstanceOf(PassengerLog::class, $result);
        $this->assertEquals(-3, $result->alighting_count);
    }

    /**
     * Test capacity detection at exact limit
     */
    public function test_capacity_detection_at_exact_limit(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
        ]);

        // Record exactly at capacity
        $result = $this->tripService->recordPassengerCount($trip, $stop, 20, 20, 0);

        // Should NOT be flagged as over capacity (at capacity is acceptable)
        $this->assertFalse($result->is_over_capacity);
        
        // But checkCapacity should return true (at or over)
        $trip->refresh();
        $this->assertTrue($this->tripService->checkCapacity($trip));
    }

    /**
     * Test capacity detection over limit
     */
    public function test_capacity_detection_over_limit_by_one(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
        ]);

        // Record one over capacity
        $result = $this->tripService->recordPassengerCount($trip, $stop, 21, 21, 0);

        // Should be flagged as over capacity
        $this->assertTrue($result->is_over_capacity);
        
        $trip->refresh();
        $this->assertTrue($this->tripService->checkCapacity($trip));
    }

    public function test_capacity_detection_significantly_over_limit(): void
    {
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
        ]);

        // Record significantly over capacity
        $result = $this->tripService->recordPassengerCount($trip, $stop, 30, 30, 0);

        // Should be flagged as over capacity
        $this->assertTrue($result->is_over_capacity);
        
        $trip->refresh();
        $this->assertTrue($this->tripService->checkCapacity($trip));
        $this->assertEquals(30, $trip->current_passenger_count);
        $this->assertEquals(30, $trip->max_passenger_count);
    }
}
