<?php

namespace Tests\Feature;

use App\Models\Ejeep;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for Reporting System
 * 
 * These tests validate universal correctness properties for report generation
 * across multiple randomly generated inputs to ensure the ReportService behaves
 * correctly for all valid report types and date ranges.
 * 
 * Note: These tests manually manage database state to support property-based
 * testing with 100+ iterations per test method.
 */
class ReportingPropertyTest extends TestCase
{
    protected ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for each test
        Artisan::call('migrate:fresh');
        
        $this->reportService = new ReportService();
    }

    protected function tearDown(): void
    {
        // Reset Faker unique generator to avoid overflow
        fake()->unique(true);
        
        parent::tearDown();
    }

    /**
     * Property 36: Daily reports include all trips
     * Validates: Requirements 7.1
     * 
     * For any date, generating a daily report should include all trips 
     * with actual_start_time on that date.
     */
    public function test_property_daily_reports_include_all_trips(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Reset Faker unique generator for each iteration
            fake()->unique(true);
            
            // Generate random date within last 30 days
            $reportDate = Carbon::now()->subDays(fake()->numberBetween(0, 30))->startOfDay();
            
            // Create random number of trips for this date
            $tripCount = fake()->numberBetween(1, 10);
            $createdTrips = [];
            
            for ($j = 0; $j < $tripCount; $j++) {
                $driver = User::factory()->driver()->create();
                $ejeep = Ejeep::factory()->create();
                $route = Route::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                ]);
                
                // Create trip with actual_start_time on the report date
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                    'status' => fake()->randomElement(['in_progress', 'completed', 'cancelled']),
                    'actual_start_time' => $reportDate->copy()->addHours(fake()->numberBetween(6, 18)),
                ]);
                
                $createdTrips[] = [
                    'trip' => $trip,
                    'schedule' => $schedule,
                    'route' => $route,
                    'ejeep' => $ejeep,
                    'driver' => $driver,
                ];
            }
            
            // Generate daily report
            $report = $this->reportService->generateDailyReport($reportDate);
            
            // Assert report includes all trips for this date
            $this->assertEquals(
                $tripCount,
                $report['total_trips'],
                "Daily report should include all {$tripCount} trips for the date"
            );
            
            // Assert report has correct date
            $this->assertEquals(
                $reportDate->format('Y-m-d'),
                $report['date'],
                "Report date should match requested date"
            );
            
            // Clean up for next iteration
            foreach ($createdTrips as $entities) {
                DB::table('trips')->where('id', $entities['trip']->id)->delete();
                DB::table('schedules')->where('id', $entities['schedule']->id)->delete();
                DB::table('routes')->where('id', $entities['route']->id)->delete();
                DB::table('ejeeps')->where('id', $entities['ejeep']->id)->delete();
                DB::table('users')->where('id', $entities['driver']->id)->delete();
            }
        }
    }

    /**
     * Property 37: Weekly reports aggregate correctly
     * Validates: Requirements 7.2
     * 
     * For any week date range, generating a weekly report should aggregate 
     * data from all trips within that range.
     */
    public function test_property_weekly_reports_aggregate_correctly(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Reset Faker unique generator for each iteration
            fake()->unique(true);
            
            // Generate random week range
            $startDate = Carbon::now()->subDays(fake()->numberBetween(7, 60))->startOfDay();
            $endDate = $startDate->copy()->addDays(6)->endOfDay();
            
            // Create random number of trips within the week
            $tripCount = fake()->numberBetween(5, 20);
            $createdTrips = [];
            
            for ($j = 0; $j < $tripCount; $j++) {
                $driver = User::factory()->driver()->create();
                $ejeep = Ejeep::factory()->create();
                $route = Route::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                ]);
                
                // Create trip with actual_start_time within the week range
                $tripDate = $startDate->copy()->addDays(fake()->numberBetween(0, 6));
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                    'status' => fake()->randomElement(['in_progress', 'completed', 'cancelled']),
                    'actual_start_time' => $tripDate->copy()->addHours(fake()->numberBetween(6, 18)),
                ]);
                
                $createdTrips[] = [
                    'trip' => $trip,
                    'schedule' => $schedule,
                    'route' => $route,
                    'ejeep' => $ejeep,
                    'driver' => $driver,
                ];
            }
            
            // Generate weekly report
            $report = $this->reportService->generateWeeklyReport($startDate, $endDate);
            
            // Assert report aggregates all trips in the range
            $this->assertEquals(
                $tripCount,
                $report['total_trips'],
                "Weekly report should aggregate all {$tripCount} trips in the range"
            );
            
            // Assert report has correct date range
            $this->assertEquals(
                $startDate->format('Y-m-d'),
                $report['start_date'],
                "Report start_date should match requested start"
            );
            $this->assertEquals(
                $endDate->format('Y-m-d'),
                $report['end_date'],
                "Report end_date should match requested end"
            );
            
            // Clean up for next iteration
            foreach ($createdTrips as $entities) {
                DB::table('trips')->where('id', $entities['trip']->id)->delete();
                DB::table('schedules')->where('id', $entities['schedule']->id)->delete();
                DB::table('routes')->where('id', $entities['route']->id)->delete();
                DB::table('ejeeps')->where('id', $entities['ejeep']->id)->delete();
                DB::table('users')->where('id', $entities['driver']->id)->delete();
            }
        }
    }

    /**
     * Property 38: Reports include route efficiency
     * Validates: Requirements 7.3
     * 
     * For any generated report, it should contain route efficiency metrics 
     * including average trip duration and on-time percentage per route.
     */
    public function test_property_reports_include_route_efficiency(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Reset Faker unique generator for each iteration
            fake()->unique(true);
            
            $reportDate = Carbon::now()->subDays(fake()->numberBetween(0, 30))->startOfDay();
            
            // Create trips for multiple routes
            $routeCount = fake()->numberBetween(1, 3);
            $createdTrips = [];
            
            for ($r = 0; $r < $routeCount; $r++) {
                $route = Route::factory()->create();
                
                // Create 2-5 trips per route
                $tripsPerRoute = fake()->numberBetween(2, 5);
                for ($j = 0; $j < $tripsPerRoute; $j++) {
                    $driver = User::factory()->driver()->create();
                    $ejeep = Ejeep::factory()->create();
                    $schedule = Schedule::factory()->create([
                        'driver_id' => $driver->id,
                        'ejeep_id' => $ejeep->id,
                        'route_id' => $route->id,
                    ]);
                    
                    $scheduledTime = $reportDate->copy()->addHours(8);
                    $actualStartTime = $scheduledTime->copy()->addMinutes(fake()->numberBetween(-10, 10));
                    $actualEndTime = $actualStartTime->copy()->addMinutes(fake()->numberBetween(30, 120));
                    
                    $trip = Trip::factory()->create([
                        'schedule_id' => $schedule->id,
                        'driver_id' => $driver->id,
                        'ejeep_id' => $ejeep->id,
                        'route_id' => $route->id,
                        'status' => 'completed',
                        'scheduled_start_time' => $scheduledTime,
                        'actual_start_time' => $actualStartTime,
                        'actual_end_time' => $actualEndTime,
                    ]);
                    
                    $createdTrips[] = [
                        'trip' => $trip,
                        'schedule' => $schedule,
                        'route' => $route,
                        'ejeep' => $ejeep,
                        'driver' => $driver,
                    ];
                }
            }
            
            // Generate daily report
            $report = $this->reportService->generateDailyReport($reportDate);
            
            // Assert route_efficiency is present
            $this->assertArrayHasKey(
                'route_efficiency',
                $report,
                "Report should include route_efficiency metrics"
            );
            
            // Assert route_efficiency is an array
            $this->assertIsArray(
                $report['route_efficiency'],
                "route_efficiency should be an array"
            );
            
            // Assert each route has efficiency metrics
            foreach ($report['route_efficiency'] as $routeMetrics) {
                $this->assertArrayHasKey('route_name', $routeMetrics);
                $this->assertArrayHasKey('average_duration_minutes', $routeMetrics);
                $this->assertArrayHasKey('on_time_percentage', $routeMetrics);
                $this->assertArrayHasKey('total_trips', $routeMetrics);
                $this->assertArrayHasKey('completed_trips', $routeMetrics);
            }
            
            // Clean up for next iteration
            foreach ($createdTrips as $entities) {
                DB::table('trips')->where('id', $entities['trip']->id)->delete();
                DB::table('schedules')->where('id', $entities['schedule']->id)->delete();
                DB::table('routes')->where('id', $entities['route']->id)->delete();
                DB::table('ejeeps')->where('id', $entities['ejeep']->id)->delete();
                DB::table('users')->where('id', $entities['driver']->id)->delete();
            }
        }
    }

    /**
     * Property 39: Reports include driver performance
     * Validates: Requirements 7.4
     * 
     * For any generated report, it should contain driver performance data 
     * including completed trips and schedule adherence per driver.
     */
    public function test_property_reports_include_driver_performance(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Reset Faker unique generator for each iteration
            fake()->unique(true);
            
            $reportDate = Carbon::now()->subDays(fake()->numberBetween(0, 30))->startOfDay();
            
            // Create trips for multiple drivers
            $driverCount = fake()->numberBetween(1, 3);
            $createdTrips = [];
            
            for ($d = 0; $d < $driverCount; $d++) {
                $driver = User::factory()->driver()->create();
                
                // Create 2-5 trips per driver
                $tripsPerDriver = fake()->numberBetween(2, 5);
                for ($j = 0; $j < $tripsPerDriver; $j++) {
                    $ejeep = Ejeep::factory()->create();
                    $route = Route::factory()->create();
                    $schedule = Schedule::factory()->create([
                        'driver_id' => $driver->id,
                        'ejeep_id' => $ejeep->id,
                        'route_id' => $route->id,
                    ]);
                    
                    $scheduledTime = $reportDate->copy()->addHours(8);
                    $actualStartTime = $scheduledTime->copy()->addMinutes(fake()->numberBetween(-10, 10));
                    
                    $trip = Trip::factory()->create([
                        'schedule_id' => $schedule->id,
                        'driver_id' => $driver->id,
                        'ejeep_id' => $ejeep->id,
                        'route_id' => $route->id,
                        'status' => fake()->randomElement(['completed', 'cancelled']),
                        'scheduled_start_time' => $scheduledTime,
                        'actual_start_time' => $actualStartTime,
                        'max_passenger_count' => fake()->numberBetween(5, 30),
                    ]);
                    
                    $createdTrips[] = [
                        'trip' => $trip,
                        'schedule' => $schedule,
                        'route' => $route,
                        'ejeep' => $ejeep,
                        'driver' => $driver,
                    ];
                }
            }
            
            // Generate daily report
            $report = $this->reportService->generateDailyReport($reportDate);
            
            // Assert driver_performance is present
            $this->assertArrayHasKey(
                'driver_performance',
                $report,
                "Report should include driver_performance metrics"
            );
            
            // Assert driver_performance is an array
            $this->assertIsArray(
                $report['driver_performance'],
                "driver_performance should be an array"
            );
            
            // Assert each driver has performance metrics
            foreach ($report['driver_performance'] as $driverMetrics) {
                $this->assertArrayHasKey('driver_name', $driverMetrics);
                $this->assertArrayHasKey('total_trips', $driverMetrics);
                $this->assertArrayHasKey('completed_trips', $driverMetrics);
                $this->assertArrayHasKey('schedule_adherence_percentage', $driverMetrics);
                $this->assertArrayHasKey('average_passenger_load', $driverMetrics);
            }
            
            // Clean up for next iteration
            foreach ($createdTrips as $entities) {
                DB::table('trips')->where('id', $entities['trip']->id)->delete();
                DB::table('schedules')->where('id', $entities['schedule']->id)->delete();
                DB::table('routes')->where('id', $entities['route']->id)->delete();
                DB::table('ejeeps')->where('id', $entities['ejeep']->id)->delete();
                DB::table('users')->where('id', $entities['driver']->id)->delete();
            }
        }
    }

    /**
     * Property 40: Schedule compliance calculated correctly
     * Validates: Requirements 7.5
     * 
     * For any date range, schedule compliance rate should equal 
     * (trips started within 5 minutes of scheduled time / total trips) * 100.
     */
    public function test_property_schedule_compliance_calculated_correctly(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Reset Faker unique generator for each iteration
            fake()->unique(true);
            
            $reportDate = Carbon::now()->subDays(fake()->numberBetween(0, 30))->startOfDay();
            
            // Create trips with known on-time status
            $totalTrips = fake()->numberBetween(5, 15);
            $onTimeTrips = fake()->numberBetween(0, $totalTrips);
            $createdTrips = [];
            
            for ($j = 0; $j < $totalTrips; $j++) {
                $driver = User::factory()->driver()->create();
                $ejeep = Ejeep::factory()->create();
                $route = Route::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                ]);
                
                $scheduledTime = $reportDate->copy()->addHours(8);
                
                // First $onTimeTrips are on-time (within 5 minutes)
                if ($j < $onTimeTrips) {
                    $actualStartTime = $scheduledTime->copy()->addMinutes(fake()->numberBetween(-5, 5));
                } else {
                    // Rest are late (more than 5 minutes)
                    $actualStartTime = $scheduledTime->copy()->addMinutes(fake()->numberBetween(6, 30));
                }
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                    'status' => 'completed',
                    'scheduled_start_time' => $scheduledTime,
                    'actual_start_time' => $actualStartTime,
                ]);
                
                $createdTrips[] = [
                    'trip' => $trip,
                    'schedule' => $schedule,
                    'route' => $route,
                    'ejeep' => $ejeep,
                    'driver' => $driver,
                ];
            }
            
            // Generate daily report
            $report = $this->reportService->generateDailyReport($reportDate);
            
            // Calculate expected compliance rate
            $expectedCompliance = $totalTrips > 0 
                ? round(($onTimeTrips / $totalTrips) * 100, 2)
                : 0.0;
            
            // Assert schedule_compliance is calculated correctly
            $this->assertEquals(
                $expectedCompliance,
                $report['schedule_compliance'],
                "Schedule compliance should be calculated as (on-time trips / total trips) * 100"
            );
            
            // Clean up for next iteration
            foreach ($createdTrips as $entities) {
                DB::table('trips')->where('id', $entities['trip']->id)->delete();
                DB::table('schedules')->where('id', $entities['schedule']->id)->delete();
                DB::table('routes')->where('id', $entities['route']->id)->delete();
                DB::table('ejeeps')->where('id', $entities['ejeep']->id)->delete();
                DB::table('users')->where('id', $entities['driver']->id)->delete();
            }
        }
    }

    /**
     * Property 41: Reports include capacity statistics
     * Validates: Requirements 7.6
     * 
     * For any generated report, it should contain passenger capacity statistics 
     * including average load, max load, and overcrowding incidents.
     */
    public function test_property_reports_include_capacity_statistics(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Reset Faker unique generator for each iteration
            fake()->unique(true);
            
            $reportDate = Carbon::now()->subDays(fake()->numberBetween(0, 30))->startOfDay();
            
            // Create trips with various passenger loads
            $tripCount = fake()->numberBetween(3, 10);
            $createdTrips = [];
            
            for ($j = 0; $j < $tripCount; $j++) {
                $driver = User::factory()->driver()->create();
                $capacity = fake()->numberBetween(15, 30);
                $ejeep = Ejeep::factory()->create(['passenger_capacity' => $capacity]);
                $route = Route::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                ]);
                
                // Some trips at/over capacity, some under
                $maxPassengerCount = fake()->numberBetween(5, $capacity + 10);
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                    'status' => 'completed',
                    'actual_start_time' => $reportDate->copy()->addHours(8),
                    'max_passenger_count' => $maxPassengerCount,
                ]);
                
                $createdTrips[] = [
                    'trip' => $trip,
                    'schedule' => $schedule,
                    'route' => $route,
                    'ejeep' => $ejeep,
                    'driver' => $driver,
                ];
            }
            
            // Generate daily report
            $report = $this->reportService->generateDailyReport($reportDate);
            
            // Assert capacity_statistics is present
            $this->assertArrayHasKey(
                'capacity_statistics',
                $report,
                "Report should include capacity_statistics"
            );
            
            // Assert capacity_statistics has required fields
            $this->assertArrayHasKey('average_load', $report['capacity_statistics']);
            $this->assertArrayHasKey('max_load', $report['capacity_statistics']);
            $this->assertArrayHasKey('overcrowding_incidents', $report['capacity_statistics']);
            
            // Assert values are numeric
            $this->assertIsNumeric($report['capacity_statistics']['average_load']);
            $this->assertIsNumeric($report['capacity_statistics']['max_load']);
            $this->assertIsInt($report['capacity_statistics']['overcrowding_incidents']);
            
            // Assert overcrowding_incidents is non-negative
            $this->assertGreaterThanOrEqual(
                0,
                $report['capacity_statistics']['overcrowding_incidents'],
                "Overcrowding incidents should be non-negative"
            );
            
            // Clean up for next iteration
            foreach ($createdTrips as $entities) {
                DB::table('trips')->where('id', $entities['trip']->id)->delete();
                DB::table('schedules')->where('id', $entities['schedule']->id)->delete();
                DB::table('routes')->where('id', $entities['route']->id)->delete();
                DB::table('ejeeps')->where('id', $entities['ejeep']->id)->delete();
                DB::table('users')->where('id', $entities['driver']->id)->delete();
            }
        }
    }

    /**
     * Property 15: Driver performance metrics calculated correctly
     * Validates: Requirements 3.7
     * 
     * For any driver and date range, performance metrics should accurately 
     * reflect completed trips, on-time percentage, and average passenger load 
     * from trip data.
     */
    public function test_property_driver_performance_metrics_calculated_correctly(): void
    {
        // Run property test with 50 iterations
        for ($i = 0; $i < 50; $i++) {
            // Reset Faker unique generator for each iteration
            fake()->unique(true);
            
            $startDate = Carbon::now()->subDays(fake()->numberBetween(7, 30))->startOfDay();
            $endDate = $startDate->copy()->addDays(6)->endOfDay();
            
            // Create a driver with known trip data
            $driver = User::factory()->driver()->create();
            
            $totalTrips = fake()->numberBetween(5, 15);
            $completedTrips = fake()->numberBetween(3, $totalTrips);
            $onTimeTrips = fake()->numberBetween(0, $completedTrips);
            
            $passengerLoads = [];
            $createdTrips = [];
            
            for ($j = 0; $j < $totalTrips; $j++) {
                $ejeep = Ejeep::factory()->create();
                $route = Route::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                ]);
                
                $tripDate = $startDate->copy()->addDays(fake()->numberBetween(0, 6));
                $scheduledTime = $tripDate->copy()->addHours(8);
                
                // Determine if this trip is completed
                $isCompleted = $j < $completedTrips;
                $status = $isCompleted ? 'completed' : 'cancelled';
                
                // Determine if this completed trip is on-time
                $isOnTime = $isCompleted && ($j < $onTimeTrips);
                $actualStartTime = $isOnTime
                    ? $scheduledTime->copy()->addMinutes(fake()->numberBetween(-5, 5))
                    : $scheduledTime->copy()->addMinutes(fake()->numberBetween(6, 30));
                
                $maxPassengerCount = fake()->numberBetween(5, 30);
                if ($isCompleted) {
                    $passengerLoads[] = $maxPassengerCount;
                }
                
                $trip = Trip::factory()->create([
                    'schedule_id' => $schedule->id,
                    'driver_id' => $driver->id,
                    'ejeep_id' => $ejeep->id,
                    'route_id' => $route->id,
                    'status' => $status,
                    'scheduled_start_time' => $scheduledTime,
                    'actual_start_time' => $actualStartTime,
                    'max_passenger_count' => $maxPassengerCount,
                ]);
                
                $createdTrips[] = [
                    'trip' => $trip,
                    'schedule' => $schedule,
                    'route' => $route,
                    'ejeep' => $ejeep,
                ];
            }
            
            // Calculate driver performance
            $performance = $this->reportService->calculateDriverPerformance($driver, $startDate, $endDate);
            
            // Assert total trips count
            $this->assertEquals(
                $totalTrips,
                $performance['total_trips'],
                "Driver performance should show correct total trips"
            );
            
            // Assert completed trips count
            $this->assertEquals(
                $completedTrips,
                $performance['completed_trips'],
                "Driver performance should show correct completed trips"
            );
            
            // Assert cancelled trips count
            $this->assertEquals(
                $totalTrips - $completedTrips,
                $performance['cancelled_trips'],
                "Driver performance should show correct cancelled trips"
            );
            
            // Assert schedule adherence percentage
            $expectedAdherence = $completedTrips > 0
                ? round(($onTimeTrips / $completedTrips) * 100, 2)
                : 0.0;
            $this->assertEquals(
                $expectedAdherence,
                $performance['schedule_adherence_percentage'],
                "Driver performance should calculate schedule adherence correctly"
            );
            
            // Assert average passenger load
            $expectedAvgLoad = count($passengerLoads) > 0
                ? array_sum($passengerLoads) / count($passengerLoads)
                : 0;
            $this->assertEquals(
                $expectedAvgLoad,
                $performance['average_passenger_load'],
                "Driver performance should calculate average passenger load correctly"
            );
            
            // Clean up for next iteration
            foreach ($createdTrips as $entities) {
                DB::table('trips')->where('id', $entities['trip']->id)->delete();
                DB::table('schedules')->where('id', $entities['schedule']->id)->delete();
                DB::table('routes')->where('id', $entities['route']->id)->delete();
                DB::table('ejeeps')->where('id', $entities['ejeep']->id)->delete();
            }
            DB::table('users')->where('id', $driver->id)->delete();
        }
    }
}
