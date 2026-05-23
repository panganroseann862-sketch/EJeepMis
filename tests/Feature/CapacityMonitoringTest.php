<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\Notification;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapacityMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected TripService $tripService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tripService = new TripService();
    }

    public function test_capacity_monitoring_flow_when_at_capacity(): void
    {
        // Arrange: Create test data
        $driver = User::factory()->driver()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
            'current_passenger_count' => 0,
            'max_passenger_count' => 0,
        ]);

        // Act: Record passenger count at capacity
        $passengerLog = $this->tripService->recordPassengerCount($trip, $stop, 20, 20, 0);

        // Assert: Verify passenger log is created correctly
        $this->assertInstanceOf(PassengerLog::class, $passengerLog);
        $this->assertEquals(20, $passengerLog->passenger_count);
        $this->assertFalse($passengerLog->is_over_capacity); // At capacity, not over

        // Assert: Verify trip is updated
        $trip->refresh();
        $this->assertEquals(20, $trip->current_passenger_count);
        $this->assertEquals(20, $trip->max_passenger_count);

        // Assert: Verify notification is sent
        $notification = Notification::where('user_id', $driver->id)
            ->where('type', 'capacity_warning')
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('reached or exceeded capacity', $notification->message);
    }

    public function test_capacity_monitoring_flow_when_over_capacity(): void
    {
        // Arrange: Create test data
        $driver = User::factory()->driver()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
            'current_passenger_count' => 0,
            'max_passenger_count' => 0,
        ]);

        // Act: Record passenger count over capacity
        $passengerLog = $this->tripService->recordPassengerCount($trip, $stop, 25, 25, 0);

        // Assert: Verify passenger log flags over capacity
        $this->assertTrue($passengerLog->is_over_capacity);
        $this->assertEquals(25, $passengerLog->passenger_count);

        // Assert: Verify trip max passenger count is updated
        $trip->refresh();
        $this->assertEquals(25, $trip->current_passenger_count);
        $this->assertEquals(25, $trip->max_passenger_count);

        // Assert: Verify notification is sent
        $notification = Notification::where('user_id', $driver->id)
            ->where('type', 'capacity_warning')
            ->first();
        $this->assertNotNull($notification);
        $this->assertEquals('Capacity Warning', $notification->title);
    }

    public function test_max_passenger_count_tracks_highest_value(): void
    {
        // Arrange: Create test data
        $driver = User::factory()->driver()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop1 = Stop::factory()->create(['route_id' => $route->id, 'sequence_order' => 1]);
        $stop2 = Stop::factory()->create(['route_id' => $route->id, 'sequence_order' => 2]);
        $stop3 = Stop::factory()->create(['route_id' => $route->id, 'sequence_order' => 3]);
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
            'current_passenger_count' => 0,
            'max_passenger_count' => 0,
        ]);

        // Act: Record passenger counts at different stops
        $this->tripService->recordPassengerCount($trip, $stop1, 15, 15, 0);
        $trip->refresh();
        $this->assertEquals(15, $trip->max_passenger_count);

        $this->tripService->recordPassengerCount($trip, $stop2, 22, 10, 3);
        $trip->refresh();
        $this->assertEquals(22, $trip->max_passenger_count);

        $this->tripService->recordPassengerCount($trip, $stop3, 18, 5, 9);
        $trip->refresh();
        $this->assertEquals(22, $trip->max_passenger_count); // Should remain at highest value

        // Assert: Verify all passenger logs are created
        $this->assertEquals(3, PassengerLog::where('trip_id', $trip->id)->count());
    }

    public function test_no_notification_sent_when_under_capacity(): void
    {
        // Arrange: Create test data
        $driver = User::factory()->driver()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        $stop = Stop::factory()->create(['route_id' => $route->id]);
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'ejeep_id' => $ejeep->id,
            'route_id' => $route->id,
        ]);

        // Act: Record passenger count under capacity
        $this->tripService->recordPassengerCount($trip, $stop, 15, 15, 0);

        // Assert: Verify no notification is sent
        $notificationCount = Notification::where('user_id', $driver->id)
            ->where('type', 'capacity_warning')
            ->count();
        $this->assertEquals(0, $notificationCount);
    }
}
