<?php

namespace Tests\Unit\Services;

use App\Models\Ejeep;
use App\Models\Notification;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
    }

    public function test_notify_route_update_creates_notification(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create([
            'route_name' => 'Test Route',
            'route_code' => 'TR-001',
        ]);

        $notification = $this->notificationService->notifyRouteUpdate($driver, $route);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($driver->id, $notification->user_id);
        $this->assertEquals('route_update', $notification->type);
        $this->assertEquals('Route Updated', $notification->title);
        $this->assertStringContainsString('Test Route', $notification->message);
        $this->assertStringContainsString('TR-001', $notification->message);
        $this->assertFalse($notification->is_read);
    }

    public function test_notify_schedule_change_creates_notification(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create(['route_name' => 'Campus Route']);
        $ejeep = Ejeep::factory()->create();
        $schedule = Schedule::factory()->create([
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'day_of_week' => 'monday',
            'departure_time' => '08:00:00',
        ]);

        $notification = $this->notificationService->notifyScheduleChange($driver, $schedule);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($driver->id, $notification->user_id);
        $this->assertEquals('schedule_change', $notification->type);
        $this->assertEquals('Schedule Changed', $notification->title);
        $this->assertStringContainsString('Campus Route', $notification->message);
        $this->assertStringContainsString('monday', $notification->message);
        $this->assertStringContainsString('08:00', $notification->message);
        $this->assertFalse($notification->is_read);
    }

    public function test_notify_capacity_warning_creates_notification(): void
    {
        $driver = User::factory()->driver()->create();
        $route = Route::factory()->create(['route_name' => 'Main Route']);
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'current_passenger_count' => 21,
        ]);

        $notification = $this->notificationService->notifyCapacityWarning($driver, $trip);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($driver->id, $notification->user_id);
        $this->assertEquals('capacity_warning', $notification->type);
        $this->assertEquals('Capacity Warning', $notification->title);
        $this->assertStringContainsString('Main Route', $notification->message);
        $this->assertStringContainsString('21', $notification->message);
        $this->assertStringContainsString('20', $notification->message);
        $this->assertFalse($notification->is_read);
    }

    public function test_send_to_driver_creates_generic_notification(): void
    {
        $driver = User::factory()->driver()->create();

        $notification = $this->notificationService->sendToDriver(
            $driver,
            'custom_type',
            'Custom Title',
            'Custom message content',
            ['key' => 'value']
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($driver->id, $notification->user_id);
        $this->assertEquals('custom_type', $notification->type);
        $this->assertEquals('Custom Title', $notification->title);
        $this->assertEquals('Custom message content', $notification->message);
        $this->assertEquals(['key' => 'value'], $notification->data);
        $this->assertFalse($notification->is_read);
    }
}
