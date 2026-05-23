<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\Notification;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ReportService;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete trip workflow: create → start → record passengers → complete
     */
    public function test_complete_trip_workflow(): void
    {
        // Setup: Create all necessary entities
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $route = Route::factory()->create();
        
        $stops = [
            Stop::factory()->create(['route_id' => $route->id, 'sequence_order' => 1, 'stop_name' => 'Stop 1']),
            Stop::factory()->create(['route_id' => $route->id, 'sequence_order' => 2, 'stop_name' => 'Stop 2']),
            Stop::factory()->create(['route_id' => $route->id, 'sequence_order' => 3, 'stop_name' => 'Stop 3']),
        ];

        $schedule = Schedule::factory()->create([
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'day_of_week' => 'monday',
        ]);

        // Step 1: Create trip
        $trip = Trip::factory()->create([
            'schedule_id' => $schedule->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'status' => 'scheduled',
            'scheduled_start_time' => now(),
        ]);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'scheduled',
            'current_passenger_count' => 0,
        ]);

        // Step 2: Driver starts trip
        $tripService = app(TripService::class);
        $updatedTrip = $tripService->startTrip($trip);

        $this->assertEquals('in_progress', $updatedTrip->status);
        $this->assertNotNull($updatedTrip->actual_start_time);
        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'in_progress',
        ]);

        // Step 3: Record passengers at each stop
        $passengerCounts = [5, 12, 8]; // boarding at stop 1, 2, then alighting at stop 3
        
        // Stop 1: 5 passengers board
        $log1 = $tripService->recordPassengerCount($updatedTrip, $stops[0], 5, 5, 0);
        $updatedTrip->refresh();
        
        $this->assertEquals(5, $updatedTrip->current_passenger_count);
        $this->assertDatabaseHas('passenger_logs', [
            'trip_id' => $trip->id,
            'stop_id' => $stops[0]->id,
            'passenger_count' => 5,
            'boarding_count' => 5,
            'alighting_count' => 0,
        ]);

        // Stop 2: 7 more passengers board (total 12)
        $log2 = $tripService->recordPassengerCount($updatedTrip, $stops[1], 12, 7, 0);
        $updatedTrip->refresh();
        
        $this->assertEquals(12, $updatedTrip->current_passenger_count);
        $this->assertEquals(12, $updatedTrip->max_passenger_count);
        $this->assertDatabaseHas('passenger_logs', [
            'trip_id' => $trip->id,
            'stop_id' => $stops[1]->id,
            'passenger_count' => 12,
        ]);

        // Stop 3: 4 passengers alight (total 8)
        $log3 = $tripService->recordPassengerCount($updatedTrip, $stops[2], 8, 0, 4);
        $updatedTrip->refresh();
        
        $this->assertEquals(8, $updatedTrip->current_passenger_count);
        $this->assertEquals(12, $updatedTrip->max_passenger_count); // Max should remain 12
        $this->assertDatabaseHas('passenger_logs', [
            'trip_id' => $trip->id,
            'stop_id' => $stops[2]->id,
            'passenger_count' => 8,
            'alighting_count' => 4,
        ]);

        // Step 4: Complete trip
        $completedTrip = $tripService->completeTrip($updatedTrip);

        $this->assertEquals('completed', $completedTrip->status);
        $this->assertNotNull($completedTrip->actual_end_time);
        $this->assertEquals(12, $completedTrip->max_passenger_count);
        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'completed',
            'max_passenger_count' => 12,
        ]);

        // Verify all passenger logs were created
        $this->assertEquals(3, PassengerLog::where('trip_id', $trip->id)->count());
    }

    /**
     * Test notification workflow: route update → notification created → driver sees it
     */
    public function test_notification_workflow(): void
    {
        // Setup: Create driver with assigned route
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $ejeep = Ejeep::factory()->create();
        $route = Route::factory()->create(['route_name' => 'Original Route']);
        
        $schedule = Schedule::factory()->create([
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
        ]);

        // Verify no notifications initially
        $this->assertEquals(0, Notification::where('user_id', $driver->id)->count());

        // Step 1: Admin updates route
        $this->actingAs($admin);
        $response = $this->put("/admin/routes/{$route->id}", [
            'route_name' => 'Updated Route Name',
            'route_code' => $route->route_code,
            'description' => 'Updated description',
            'status' => 'active',
        ]);

        $response->assertRedirect();

        // Step 2: Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $driver->id,
            'type' => 'route_update',
            'is_read' => false,
        ]);

        $notification = Notification::where('user_id', $driver->id)
            ->where('type', 'route_update')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('route', strtolower($notification->title));

        // Step 3: Driver sees notification on dashboard
        $this->actingAs($driver);
        $dashboardResponse = $this->get('/driver/dashboard');
        
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('1'); // Unread notification count

        // Step 4: Driver views notifications
        $notificationsResponse = $this->get('/driver/notifications');
        
        $notificationsResponse->assertOk();
        $notificationsResponse->assertSee($notification->title);
        $notificationsResponse->assertSee($notification->message);

        // Step 5: Driver marks notification as read
        $markReadResponse = $this->post("/driver/notifications/{$notification->id}/mark-as-read");
        
        $markReadResponse->assertOk();
        
        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);

        // Step 6: Verify notification history is maintained
        $this->assertEquals(1, Notification::where('user_id', $driver->id)->count());
        $this->assertTrue(Notification::where('user_id', $driver->id)->first()->is_read);
    }

    /**
     * Test capacity alert workflow: passenger count exceeds → alert generated → admin sees it
     */
    public function test_capacity_alert_workflow(): void
    {
        // Setup: Create trip with low capacity E-Jeep
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 10]); // Small capacity
        $route = Route::factory()->create();
        
        $stop = Stop::factory()->create(['route_id' => $route->id, 'sequence_order' => 1]);

        $schedule = Schedule::factory()->create([
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
        ]);

        $trip = Trip::factory()->create([
            'schedule_id' => $schedule->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);

        // Step 1: Driver records passenger count at capacity
        $tripService = app(TripService::class);
        $log = $tripService->recordPassengerCount($trip, $stop, 10, 10, 0);
        
        $trip->refresh();
        $this->assertEquals(10, $trip->current_passenger_count);

        // Step 2: Verify capacity alert notification was generated
        $this->assertDatabaseHas('notifications', [
            'user_id' => $driver->id,
            'type' => 'capacity_warning',
        ]);

        // Step 3: Driver records passenger count exceeding capacity
        $log2 = $tripService->recordPassengerCount($trip, $stop, 12, 2, 0);
        
        $trip->refresh();
        $this->assertEquals(12, $trip->current_passenger_count);

        // Step 4: Verify overcapacity flag is set
        $this->assertDatabaseHas('passenger_logs', [
            'trip_id' => $trip->id,
            'passenger_count' => 12,
            'is_over_capacity' => true,
        ]);

        // Step 5: Admin views dashboard and sees capacity alert
        $this->actingAs($admin);
        $dashboardResponse = $this->get('/admin/dashboard');
        
        $dashboardResponse->assertOk();

        // Step 6: Admin checks real-time data API
        $realtimeResponse = $this->get('/admin/dashboard/realtime-data');
        
        $realtimeResponse->assertOk();
        $data = $realtimeResponse->json();
        
        $this->assertArrayHasKey('capacityAlerts', $data);
        $this->assertGreaterThan(0, count($data['capacityAlerts']));
        
        // Verify the trip appears in capacity alerts
        $alertFound = false;
        foreach ($data['capacityAlerts'] as $alert) {
            if ($alert['id'] === $trip->id) {
                $alertFound = true;
                $this->assertEquals(12, $alert['current_passenger_count']);
                $this->assertEquals(10, $alert['passenger_capacity']);
            }
        }
        $this->assertTrue($alertFound, 'Trip should appear in capacity alerts');

        // Step 7: Admin views trip monitoring page
        $monitoringResponse = $this->get('/admin/trips');
        
        $monitoringResponse->assertOk();
        $monitoringResponse->assertSee($ejeep->vehicle_number);

        // Step 8: Admin views detailed trip information
        $tripDetailResponse = $this->get("/admin/trips/{$trip->id}");
        
        $tripDetailResponse->assertOk();
        $tripDetailResponse->assertSee('12'); // Current passenger count
        $tripDetailResponse->assertSee('10'); // Capacity
    }

    /**
     * Test report generation workflow: create trips → generate report → verify data
     */
    public function test_report_generation_workflow(): void
    {
        // Setup: Create multiple completed trips
        $admin = User::factory()->admin()->create();
        $driver1 = User::factory()->driver()->create(['first_name' => 'John', 'last_name' => 'Driver']);
        $driver2 = User::factory()->driver()->create(['first_name' => 'Jane', 'last_name' => 'Driver']);
        
        $ejeep1 = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $ejeep2 = Ejeep::factory()->create(['passenger_capacity' => 25]);
        
        $route1 = Route::factory()->create(['route_name' => 'Route A']);
        $route2 = Route::factory()->create(['route_name' => 'Route B']);

        $today = now()->startOfDay();

        // Create schedules
        $schedule1 = Schedule::factory()->create([
            'route_id' => $route1->id,
            'ejeep_id' => $ejeep1->id,
            'driver_id' => $driver1->id,
            'departure_time' => '08:00:00',
        ]);

        $schedule2 = Schedule::factory()->create([
            'route_id' => $route2->id,
            'ejeep_id' => $ejeep2->id,
            'driver_id' => $driver2->id,
            'departure_time' => '09:00:00',
        ]);

        // Step 1: Create completed trips with passenger data
        $trip1 = Trip::factory()->create([
            'schedule_id' => $schedule1->id,
            'ejeep_id' => $ejeep1->id,
            'driver_id' => $driver1->id,
            'route_id' => $route1->id,
            'status' => 'completed',
            'scheduled_start_time' => $today->copy()->setTime(8, 0),
            'actual_start_time' => $today->copy()->setTime(8, 10), // 10 minutes late - NOT on time
            'actual_end_time' => $today->copy()->setTime(8, 45),
            'current_passenger_count' => 0,
            'max_passenger_count' => 15,
        ]);

        $trip2 = Trip::factory()->create([
            'schedule_id' => $schedule2->id,
            'ejeep_id' => $ejeep2->id,
            'driver_id' => $driver2->id,
            'route_id' => $route2->id,
            'status' => 'completed',
            'scheduled_start_time' => $today->copy()->setTime(9, 0),
            'actual_start_time' => $today->copy()->setTime(9, 2), // 2 minutes late - on time
            'actual_end_time' => $today->copy()->setTime(9, 50),
            'current_passenger_count' => 0,
            'max_passenger_count' => 22,
        ]);

        $trip3 = Trip::factory()->create([
            'schedule_id' => $schedule1->id,
            'ejeep_id' => $ejeep1->id,
            'driver_id' => $driver1->id,
            'route_id' => $route1->id,
            'status' => 'completed',
            'scheduled_start_time' => $today->copy()->setTime(10, 0),
            'actual_start_time' => $today->copy()->setTime(10, 8), // 8 minutes late - NOT on time
            'actual_end_time' => $today->copy()->setTime(10, 40),
            'current_passenger_count' => 0,
            'max_passenger_count' => 18,
        ]);

        // Step 2: Admin navigates to reports page
        $this->actingAs($admin);
        $reportsIndexResponse = $this->get('/admin/reports');
        
        $reportsIndexResponse->assertOk();
        $reportsIndexResponse->assertSee('Generate Report');

        // Step 3: Generate daily report
        $reportService = app(ReportService::class);
        $dailyReport = $reportService->generateDailyReport($today);

        // Step 4: Verify report includes all trips
        $this->assertArrayHasKey('total_trips', $dailyReport);
        $this->assertEquals(3, $dailyReport['total_trips']);

        // Verify route efficiency metrics
        $this->assertArrayHasKey('route_efficiency', $dailyReport);
        
        $routeEfficiency = collect($dailyReport['route_efficiency']);
        $route1Efficiency = $routeEfficiency->firstWhere('route_name', $route1->route_name);
        $route2Efficiency = $routeEfficiency->firstWhere('route_name', $route2->route_name);

        $this->assertNotNull($route1Efficiency);
        $this->assertNotNull($route2Efficiency);
        $this->assertEquals(2, $route1Efficiency['total_trips']); // 2 trips on route 1
        $this->assertEquals(1, $route2Efficiency['total_trips']); // 1 trip on route 2

        // Step 5: Verify driver performance metrics
        $this->assertArrayHasKey('driver_performance', $dailyReport);
        
        $driverPerformance = collect($dailyReport['driver_performance']);
        $driver1Performance = $driverPerformance->firstWhere('driver_name', $driver1->first_name . ' ' . $driver1->last_name);
        $driver2Performance = $driverPerformance->firstWhere('driver_name', $driver2->first_name . ' ' . $driver2->last_name);

        $this->assertNotNull($driver1Performance);
        $this->assertNotNull($driver2Performance);
        $this->assertEquals(2, $driver1Performance['completed_trips']);
        $this->assertEquals(1, $driver2Performance['completed_trips']);

        // Step 6: Verify schedule compliance calculation
        $this->assertArrayHasKey('schedule_compliance', $dailyReport);
        
        // 1 out of 3 trips was on time (within 5 minutes)
        $expectedCompliance = (1 / 3) * 100;
        $this->assertEquals(round($expectedCompliance, 2), $dailyReport['schedule_compliance']);

        // Step 7: Verify capacity statistics
        $this->assertArrayHasKey('capacity_statistics', $dailyReport);
        
        $capacityStats = $dailyReport['capacity_statistics'];
        $this->assertArrayHasKey('average_load', $capacityStats);
        $this->assertArrayHasKey('max_load', $capacityStats);
        
        // Average of 15, 22, 18 = 18.33
        $this->assertEquals(18.33, $capacityStats['average_load']);
        $this->assertEquals(22, $capacityStats['max_load']);

        // Step 8: Generate weekly report
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();
        $weeklyReport = $reportService->generateWeeklyReport($weekStart, $weekEnd);

        // Step 9: Verify weekly report aggregates correctly
        $this->assertArrayHasKey('total_trips', $weeklyReport);
        $this->assertEquals(3, $weeklyReport['total_trips']);
        $this->assertArrayHasKey('route_efficiency', $weeklyReport);
        $this->assertArrayHasKey('driver_performance', $weeklyReport);
        $this->assertArrayHasKey('schedule_compliance', $weeklyReport);
        $this->assertArrayHasKey('capacity_statistics', $weeklyReport);

        // Step 10: Admin generates report via HTTP request
        $generateResponse = $this->post('/admin/reports/daily', [
            'date' => $today->format('Y-m-d'),
            'format' => 'json',
        ]);

        $generateResponse->assertOk();
        
        // Verify response contains report data
        $responseData = $generateResponse->json();
        $this->assertArrayHasKey('total_trips', $responseData);
        $this->assertEquals(3, $responseData['total_trips']);
    }
}
