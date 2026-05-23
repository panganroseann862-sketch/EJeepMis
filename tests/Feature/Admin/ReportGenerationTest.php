<?php

namespace Tests\Feature\Admin;

use App\Models\Ejeep;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $driver;
    private Ejeep $ejeep;
    private Route $route;
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->driver = User::factory()->driver()->create();
        $this->ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $this->route = Route::factory()->create();
        
        $stop = Stop::factory()->create([
            'route_id' => $this->route->id,
            'sequence_order' => 1,
        ]);

        $this->schedule = Schedule::factory()->create([
            'route_id' => $this->route->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
        ]);
    }

    public function test_admin_can_access_reports_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('Generate Reports');
        $response->assertSee('Daily Report');
        $response->assertSee('Weekly Report');
    }

    public function test_driver_cannot_access_reports_page(): void
    {
        $response = $this->actingAs($this->driver)->get(route('admin.reports.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_generate_daily_report(): void
    {
        $date = Carbon::today();
        
        // Create a completed trip for today
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'scheduled_start_time' => $date->copy()->setTime(8, 0),
            'actual_start_time' => $date->copy()->setTime(8, 2),
            'actual_end_time' => $date->copy()->setTime(9, 0),
            'max_passenger_count' => 15,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'date' => $date->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals($date->format('Y-m-d'), $data['date']);
        $this->assertEquals(1, $data['total_trips']);
        $this->assertEquals(1, $data['completed_trips']);
        $this->assertEquals(0, $data['cancelled_trips']);
    }

    public function test_daily_report_includes_all_trips_for_date(): void
    {
        $date = Carbon::today();
        
        // Create multiple trips for today
        Trip::factory()->count(3)->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'actual_start_time' => $date->copy()->setTime(8, 0),
            'actual_end_time' => $date->copy()->setTime(9, 0),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'date' => $date->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals(3, $data['total_trips']);
    }

    public function test_admin_can_generate_weekly_report(): void
    {
        $startDate = Carbon::today()->subDays(7);
        $endDate = Carbon::today();
        
        // Create trips within the date range
        Trip::factory()->count(5)->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'actual_start_time' => $startDate->copy()->addDays(2)->setTime(8, 0),
            'actual_end_time' => $startDate->copy()->addDays(2)->setTime(9, 0),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.weekly'), [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals($startDate->format('Y-m-d'), $data['start_date']);
        $this->assertEquals($endDate->format('Y-m-d'), $data['end_date']);
        $this->assertEquals(5, $data['total_trips']);
    }

    public function test_weekly_report_aggregates_correctly(): void
    {
        $startDate = Carbon::today()->subDays(7);
        $endDate = Carbon::today();
        
        // Create completed and cancelled trips
        Trip::factory()->count(3)->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'actual_start_time' => $startDate->copy()->addDays(2)->setTime(8, 0),
            'actual_end_time' => $startDate->copy()->addDays(2)->setTime(9, 0),
            'max_passenger_count' => 15,
        ]);

        Trip::factory()->count(2)->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'cancelled',
            'actual_start_time' => $startDate->copy()->addDays(3)->setTime(8, 0),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.weekly'), [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals(5, $data['total_trips']);
        $this->assertEquals(3, $data['completed_trips']);
        $this->assertEquals(2, $data['cancelled_trips']);
        $this->assertEquals(45, $data['total_passengers']); // 3 trips * 15 passengers
    }

    public function test_report_includes_route_efficiency(): void
    {
        $date = Carbon::today();
        
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'scheduled_start_time' => $date->copy()->setTime(8, 0),
            'actual_start_time' => $date->copy()->setTime(8, 2),
            'actual_end_time' => $date->copy()->setTime(9, 0),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'date' => $date->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertArrayHasKey('route_efficiency', $data);
        $this->assertNotEmpty($data['route_efficiency']);
        $this->assertArrayHasKey('route_name', $data['route_efficiency'][0]);
        $this->assertArrayHasKey('average_duration_minutes', $data['route_efficiency'][0]);
        $this->assertArrayHasKey('on_time_percentage', $data['route_efficiency'][0]);
    }

    public function test_report_includes_driver_performance(): void
    {
        $date = Carbon::today();
        
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'actual_start_time' => $date->copy()->setTime(8, 0),
            'actual_end_time' => $date->copy()->setTime(9, 0),
            'max_passenger_count' => 15,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'date' => $date->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertArrayHasKey('driver_performance', $data);
        $this->assertNotEmpty($data['driver_performance']);
        $this->assertArrayHasKey('driver_name', $data['driver_performance'][0]);
        $this->assertArrayHasKey('schedule_adherence_percentage', $data['driver_performance'][0]);
        $this->assertArrayHasKey('average_passenger_load', $data['driver_performance'][0]);
    }

    public function test_schedule_compliance_calculated_correctly(): void
    {
        $date = Carbon::today();
        
        // Create on-time trip (within 5 minutes)
        Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'scheduled_start_time' => $date->copy()->setTime(8, 0),
            'actual_start_time' => $date->copy()->setTime(8, 3),
            'actual_end_time' => $date->copy()->setTime(9, 0),
        ]);

        // Create late trip (more than 5 minutes)
        Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'scheduled_start_time' => $date->copy()->setTime(10, 0),
            'actual_start_time' => $date->copy()->setTime(10, 10),
            'actual_end_time' => $date->copy()->setTime(11, 0),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'date' => $date->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertOk();
        $data = $response->json();
        
        // 1 out of 2 trips on time = 50%
        $this->assertEquals(50.0, $data['schedule_compliance']);
    }

    public function test_report_includes_capacity_statistics(): void
    {
        $date = Carbon::today();
        
        // Create trip with overcrowding
        $trip = Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'actual_start_time' => $date->copy()->setTime(8, 0),
            'actual_end_time' => $date->copy()->setTime(9, 0),
            'max_passenger_count' => 25, // Exceeds capacity of 20
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'date' => $date->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertArrayHasKey('capacity_statistics', $data);
        $this->assertEquals(25, $data['capacity_statistics']['max_load']);
        $this->assertEquals(1, $data['capacity_statistics']['overcrowding_incidents']);
    }

    public function test_daily_report_validates_date_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'format' => 'json',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_daily_report_validates_future_dates(): void
    {
        $futureDate = Carbon::tomorrow();

        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'date' => $futureDate->format('Y-m-d'),
            'format' => 'json',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_weekly_report_validates_date_range(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.reports.weekly'), [
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->subDays(7)->format('Y-m-d'), // End before start
            'format' => 'json',
        ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_admin_can_export_report_as_csv(): void
    {
        $date = Carbon::today();
        
        Trip::factory()->create([
            'schedule_id' => $this->schedule->id,
            'ejeep_id' => $this->ejeep->id,
            'driver_id' => $this->driver->id,
            'route_id' => $this->route->id,
            'status' => 'completed',
            'actual_start_time' => $date->copy()->setTime(8, 0),
            'actual_end_time' => $date->copy()->setTime(9, 0),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.reports.daily'), [
            'date' => $date->format('Y-m-d'),
            'format' => 'csv',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
        
        $content = $response->getContent();
        $this->assertStringContainsString('Report Summary', $content);
        $this->assertStringContainsString('Route Efficiency', $content);
        $this->assertStringContainsString('Driver Performance', $content);
    }
}
