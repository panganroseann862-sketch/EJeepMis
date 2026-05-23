<?php

namespace Tests\Unit;

use App\Models\Ejeep;
use App\Models\Notification;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_creates_driver(): void
    {
        $driver = User::factory()->driver()->create();
        
        $this->assertEquals('driver', $driver->role);
        $this->assertNotNull($driver->username);
        $this->assertNotNull($driver->email);
    }

    public function test_user_factory_creates_admin(): void
    {
        $admin = User::factory()->admin()->create();
        
        $this->assertEquals('admin', $admin->role);
    }

    public function test_ejeep_factory_creates_vehicle(): void
    {
        $ejeep = Ejeep::factory()->create();
        
        $this->assertNotNull($ejeep->vehicle_number);
        $this->assertNotNull($ejeep->plate_number);
        $this->assertGreaterThan(0, $ejeep->passenger_capacity);
        $this->assertEquals('active', $ejeep->operational_status);
    }

    public function test_ejeep_factory_creates_maintenance_vehicle(): void
    {
        $ejeep = Ejeep::factory()->maintenance()->create();
        
        $this->assertEquals('maintenance', $ejeep->operational_status);
        $this->assertNotNull($ejeep->maintenance_notes);
    }

    public function test_route_factory_creates_route(): void
    {
        $route = Route::factory()->create();
        
        $this->assertNotNull($route->route_name);
        $this->assertNotNull($route->route_code);
        $this->assertEquals('active', $route->status);
    }

    public function test_stop_factory_creates_stop(): void
    {
        $route = Route::factory()->create();
        $stop = Stop::factory()->forRoute($route)->create();
        
        $this->assertEquals($route->id, $stop->route_id);
        $this->assertNotNull($stop->stop_name);
        $this->assertGreaterThan(0, $stop->sequence_order);
    }

    public function test_schedule_factory_creates_schedule(): void
    {
        $schedule = Schedule::factory()->create();
        
        $this->assertNotNull($schedule->route_id);
        $this->assertNotNull($schedule->ejeep_id);
        $this->assertNotNull($schedule->driver_id);
        $this->assertNotNull($schedule->departure_time);
        $this->assertNotNull($schedule->day_of_week);
    }

    public function test_trip_factory_creates_scheduled_trip(): void
    {
        $trip = Trip::factory()->create();
        
        $this->assertEquals('scheduled', $trip->status);
        $this->assertNotNull($trip->scheduled_start_time);
        $this->assertNull($trip->actual_start_time);
    }

    public function test_trip_factory_creates_in_progress_trip(): void
    {
        $trip = Trip::factory()->inProgress()->create();
        
        $this->assertEquals('in_progress', $trip->status);
        $this->assertNotNull($trip->actual_start_time);
        $this->assertGreaterThan(0, $trip->current_passenger_count);
    }

    public function test_trip_factory_creates_completed_trip(): void
    {
        $trip = Trip::factory()->completed()->create();
        
        $this->assertEquals('completed', $trip->status);
        $this->assertNotNull($trip->actual_start_time);
        $this->assertNotNull($trip->actual_end_time);
    }

    public function test_passenger_log_factory_creates_log(): void
    {
        $log = PassengerLog::factory()->create();
        
        $this->assertNotNull($log->trip_id);
        $this->assertNotNull($log->stop_id);
        $this->assertGreaterThanOrEqual(0, $log->passenger_count);
        $this->assertGreaterThanOrEqual(0, $log->boarding_count);
        $this->assertGreaterThanOrEqual(0, $log->alighting_count);
    }

    public function test_notification_factory_creates_route_update(): void
    {
        $notification = Notification::factory()->routeUpdate()->create();
        
        $this->assertEquals('route_update', $notification->type);
        $this->assertNotNull($notification->title);
        $this->assertNotNull($notification->message);
        $this->assertFalse($notification->is_read);
    }

    public function test_notification_factory_creates_schedule_change(): void
    {
        $notification = Notification::factory()->scheduleChange()->create();
        
        $this->assertEquals('schedule_change', $notification->type);
    }

    public function test_notification_factory_creates_capacity_warning(): void
    {
        $notification = Notification::factory()->capacityWarning()->create();
        
        $this->assertEquals('capacity_warning', $notification->type);
    }
}
