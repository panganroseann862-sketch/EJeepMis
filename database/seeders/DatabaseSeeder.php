<?php

namespace Database\Seeders;

use App\Models\Ejeep;
use App\Models\Notification;
use App\Models\PassengerLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin Users
        $admin1 = User::create([
            'username' => 'admin',
            'email' => 'admin@ejeep.edu',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'first_name' => 'John',
            'last_name' => 'Administrator',
            'phone' => '09171234567',
            'status' => 'active',
        ]);

        $admin2 = User::create([
            'username' => 'admin2',
            'email' => 'admin2@ejeep.edu',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'phone' => '09181234567',
            'status' => 'active',
        ]);

        // Create Driver Users
        $drivers = [];
        $driverData = [
            ['username' => 'driver1', 'first_name' => 'Pedro', 'last_name' => 'Cruz', 'phone' => '09191234567'],
            ['username' => 'driver2', 'first_name' => 'Juan', 'last_name' => 'Reyes', 'phone' => '09201234567'],
            ['username' => 'driver3', 'first_name' => 'Carlos', 'last_name' => 'Garcia', 'phone' => '09211234567'],
            ['username' => 'driver4', 'first_name' => 'Miguel', 'last_name' => 'Torres', 'phone' => '09221234567'],
            ['username' => 'driver5', 'first_name' => 'Roberto', 'last_name' => 'Flores', 'phone' => '09231234567'],
        ];

        foreach ($driverData as $data) {
            $drivers[] = User::create([
                'username' => $data['username'],
                'email' => $data['username'] . '@ejeep.edu',
                'password' => Hash::make('password'),
                'role' => 'driver',
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'status' => 'active',
            ]);
        }

        // Create E-Jeeps
        $ejeeps = [];
        $ejeepData = [
            ['vehicle_number' => 'EJ-001', 'plate_number' => 'ABC1234', 'capacity' => 20, 'status' => 'active'],
            ['vehicle_number' => 'EJ-002', 'plate_number' => 'DEF5678', 'capacity' => 22, 'status' => 'active'],
            ['vehicle_number' => 'EJ-003', 'plate_number' => 'GHI9012', 'capacity' => 18, 'status' => 'active'],
            ['vehicle_number' => 'EJ-004', 'plate_number' => 'JKL3456', 'capacity' => 20, 'status' => 'active'],
            ['vehicle_number' => 'EJ-005', 'plate_number' => 'MNO7890', 'capacity' => 25, 'status' => 'maintenance'],
        ];

        foreach ($ejeepData as $data) {
            $ejeeps[] = Ejeep::create([
                'vehicle_number' => $data['vehicle_number'],
                'plate_number' => $data['plate_number'],
                'passenger_capacity' => $data['capacity'],
                'operational_status' => $data['status'],
                'maintenance_notes' => $data['status'] === 'maintenance' ? 'Scheduled maintenance - battery check' : null,
                'last_maintenance_date' => $data['status'] === 'maintenance' ? Carbon::now()->subDays(5) : Carbon::now()->subDays(30),
            ]);
        }

        // Create Routes with Stops
        $routes = [];
        
        // Route 1: Main Campus Loop
        $route1 = Route::create([
            'route_name' => 'Main Campus Loop',
            'route_code' => 'MCL-01',
            'description' => 'Circular route covering main campus buildings',
            'status' => 'active',
        ]);
        $routes[] = $route1;

        $mainCampusStops = [
            ['name' => 'Main Gate', 'description' => 'University main entrance', 'lat' => 14.5995, 'lng' => 120.9842],
            ['name' => 'Engineering Building', 'description' => 'College of Engineering', 'lat' => 14.6001, 'lng' => 120.9850],
            ['name' => 'Library', 'description' => 'Central Library', 'lat' => 14.6008, 'lng' => 120.9855],
            ['name' => 'Student Center', 'description' => 'Student activities hub', 'lat' => 14.6012, 'lng' => 120.9848],
            ['name' => 'Cafeteria', 'description' => 'Main dining hall', 'lat' => 14.6005, 'lng' => 120.9840],
        ];

        foreach ($mainCampusStops as $index => $stop) {
            Stop::create([
                'route_id' => $route1->id,
                'stop_name' => $stop['name'],
                'location_description' => $stop['description'],
                'latitude' => $stop['lat'],
                'longitude' => $stop['lng'],
                'sequence_order' => $index + 1,
            ]);
        }

        // Route 2: Dormitory Express
        $route2 = Route::create([
            'route_name' => 'Dormitory Express',
            'route_code' => 'DORM-01',
            'description' => 'Direct route from dormitories to academic buildings',
            'status' => 'active',
        ]);
        $routes[] = $route2;

        $dormStops = [
            ['name' => 'North Dormitory', 'description' => 'Male dormitory complex', 'lat' => 14.5980, 'lng' => 120.9830],
            ['name' => 'South Dormitory', 'description' => 'Female dormitory complex', 'lat' => 14.5985, 'lng' => 120.9835],
            ['name' => 'Science Building', 'description' => 'College of Science', 'lat' => 14.6000, 'lng' => 120.9845],
            ['name' => 'Admin Building', 'description' => 'Administration offices', 'lat' => 14.6010, 'lng' => 120.9852],
        ];

        foreach ($dormStops as $index => $stop) {
            Stop::create([
                'route_id' => $route2->id,
                'stop_name' => $stop['name'],
                'location_description' => $stop['description'],
                'latitude' => $stop['lat'],
                'longitude' => $stop['lng'],
                'sequence_order' => $index + 1,
            ]);
        }

        // Route 3: Sports Complex Route
        $route3 = Route::create([
            'route_name' => 'Sports Complex Route',
            'route_code' => 'SPT-01',
            'description' => 'Route to sports facilities and gymnasium',
            'status' => 'active',
        ]);
        $routes[] = $route3;

        $sportsStops = [
            ['name' => 'Main Gate', 'description' => 'University main entrance', 'lat' => 14.5995, 'lng' => 120.9842],
            ['name' => 'Gymnasium', 'description' => 'Indoor sports facility', 'lat' => 14.5990, 'lng' => 120.9860],
            ['name' => 'Swimming Pool', 'description' => 'Olympic-size pool', 'lat' => 14.5988, 'lng' => 120.9865],
            ['name' => 'Track and Field', 'description' => 'Outdoor athletics area', 'lat' => 14.5985, 'lng' => 120.9870],
        ];

        foreach ($sportsStops as $index => $stop) {
            Stop::create([
                'route_id' => $route3->id,
                'stop_name' => $stop['name'],
                'location_description' => $stop['description'],
                'latitude' => $stop['lat'],
                'longitude' => $stop['lng'],
                'sequence_order' => $index + 1,
            ]);
        }

        // Create Schedules
        $schedules = [];
        $today = Carbon::now()->format('l'); // Get current day name
        $dayOfWeek = strtolower($today);

        // Morning schedules
        $schedule1 = Schedule::create([
            'route_id' => $route1->id,
            'ejeep_id' => $ejeeps[0]->id,
            'driver_id' => $drivers[0]->id,
            'departure_time' => '07:00:00',
            'day_of_week' => $dayOfWeek,
            'status' => 'active',
        ]);
        $schedules[] = $schedule1;

        $schedule2 = Schedule::create([
            'route_id' => $route2->id,
            'ejeep_id' => $ejeeps[1]->id,
            'driver_id' => $drivers[1]->id,
            'departure_time' => '07:30:00',
            'day_of_week' => $dayOfWeek,
            'status' => 'active',
        ]);
        $schedules[] = $schedule2;

        // Midday schedules
        $schedule3 = Schedule::create([
            'route_id' => $route1->id,
            'ejeep_id' => $ejeeps[2]->id,
            'driver_id' => $drivers[2]->id,
            'departure_time' => '12:00:00',
            'day_of_week' => $dayOfWeek,
            'status' => 'active',
        ]);
        $schedules[] = $schedule3;

        $schedule4 = Schedule::create([
            'route_id' => $route3->id,
            'ejeep_id' => $ejeeps[3]->id,
            'driver_id' => $drivers[3]->id,
            'departure_time' => '14:00:00',
            'day_of_week' => $dayOfWeek,
            'status' => 'active',
        ]);
        $schedules[] = $schedule4;

        // Create Trips with various statuses
        $trips = [];

        // Completed trip from this morning
        $trip1 = Trip::create([
            'schedule_id' => $schedule1->id,
            'ejeep_id' => $ejeeps[0]->id,
            'driver_id' => $drivers[0]->id,
            'route_id' => $route1->id,
            'status' => 'completed',
            'scheduled_start_time' => Carbon::today()->setTime(7, 0),
            'actual_start_time' => Carbon::today()->setTime(7, 2),
            'actual_end_time' => Carbon::today()->setTime(7, 35),
            'current_passenger_count' => 0,
            'max_passenger_count' => 18,
            'has_route_deviation' => false,
        ]);
        $trips[] = $trip1;

        // Add passenger logs for completed trip
        $route1Stops = $route1->stops()->orderBy('sequence_order')->get();
        PassengerLog::create([
            'trip_id' => $trip1->id,
            'stop_id' => $route1Stops[0]->id,
            'passenger_count' => 12,
            'boarding_count' => 12,
            'alighting_count' => 0,
            'is_over_capacity' => false,
            'recorded_at' => Carbon::today()->setTime(7, 5),
        ]);
        PassengerLog::create([
            'trip_id' => $trip1->id,
            'stop_id' => $route1Stops[1]->id,
            'passenger_count' => 18,
            'boarding_count' => 8,
            'alighting_count' => 2,
            'is_over_capacity' => false,
            'recorded_at' => Carbon::today()->setTime(7, 12),
        ]);
        PassengerLog::create([
            'trip_id' => $trip1->id,
            'stop_id' => $route1Stops[2]->id,
            'passenger_count' => 15,
            'boarding_count' => 3,
            'alighting_count' => 6,
            'is_over_capacity' => false,
            'recorded_at' => Carbon::today()->setTime(7, 20),
        ]);

        // In-progress trip (currently running)
        $trip2 = Trip::create([
            'schedule_id' => $schedule3->id,
            'ejeep_id' => $ejeeps[2]->id,
            'driver_id' => $drivers[2]->id,
            'route_id' => $route1->id,
            'status' => 'in_progress',
            'scheduled_start_time' => Carbon::today()->setTime(12, 0),
            'actual_start_time' => Carbon::today()->setTime(12, 1),
            'actual_end_time' => null,
            'current_passenger_count' => 22,
            'max_passenger_count' => 22,
            'has_route_deviation' => false,
        ]);
        $trips[] = $trip2;

        // Add passenger logs for in-progress trip (at capacity)
        PassengerLog::create([
            'trip_id' => $trip2->id,
            'stop_id' => $route1Stops[0]->id,
            'passenger_count' => 15,
            'boarding_count' => 15,
            'alighting_count' => 0,
            'is_over_capacity' => false,
            'recorded_at' => Carbon::today()->setTime(12, 5),
        ]);
        PassengerLog::create([
            'trip_id' => $trip2->id,
            'stop_id' => $route1Stops[1]->id,
            'passenger_count' => 22,
            'boarding_count' => 10,
            'alighting_count' => 3,
            'is_over_capacity' => true,
            'recorded_at' => Carbon::today()->setTime(12, 12),
        ]);

        // Scheduled trip (upcoming)
        $trip3 = Trip::create([
            'schedule_id' => $schedule4->id,
            'ejeep_id' => $ejeeps[3]->id,
            'driver_id' => $drivers[3]->id,
            'route_id' => $route3->id,
            'status' => 'scheduled',
            'scheduled_start_time' => Carbon::today()->setTime(14, 0),
            'actual_start_time' => null,
            'actual_end_time' => null,
            'current_passenger_count' => 0,
            'max_passenger_count' => 0,
            'has_route_deviation' => false,
        ]);
        $trips[] = $trip3;

        // Trip with route deviation
        $trip4 = Trip::create([
            'schedule_id' => $schedule2->id,
            'ejeep_id' => $ejeeps[1]->id,
            'driver_id' => $drivers[1]->id,
            'route_id' => $route2->id,
            'status' => 'completed',
            'scheduled_start_time' => Carbon::today()->setTime(7, 30),
            'actual_start_time' => Carbon::today()->setTime(7, 33),
            'actual_end_time' => Carbon::today()->setTime(8, 10),
            'current_passenger_count' => 0,
            'max_passenger_count' => 16,
            'has_route_deviation' => true,
            'deviation_notes' => 'Detoured due to road construction on main route',
        ]);
        $trips[] = $trip4;

        // Create Notifications
        Notification::create([
            'user_id' => $drivers[0]->id,
            'type' => 'route_update',
            'title' => 'Route Update',
            'message' => 'Main Campus Loop route has been updated with new stop sequence.',
            'data' => json_encode(['route_id' => $route1->id]),
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $drivers[2]->id,
            'type' => 'capacity_warning',
            'title' => 'Capacity Warning',
            'message' => 'Your current trip has reached maximum capacity.',
            'data' => json_encode(['trip_id' => $trip2->id]),
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $drivers[1]->id,
            'type' => 'schedule_change',
            'title' => 'Schedule Change',
            'message' => 'Your departure time for tomorrow has been changed to 7:45 AM.',
            'data' => json_encode(['schedule_id' => $schedule2->id]),
            'is_read' => true,
            'read_at' => Carbon::now()->subHours(2),
        ]);

        Notification::create([
            'user_id' => $drivers[3]->id,
            'type' => 'route_update',
            'title' => 'New Route Assignment',
            'message' => 'You have been assigned to Sports Complex Route starting today.',
            'data' => json_encode(['route_id' => $route3->id]),
            'is_read' => false,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Test Accounts:');
        $this->command->info('==============');
        $this->command->info('Admin Account:');
        $this->command->info('  Username: admin');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('Driver Accounts:');
        $this->command->info('  Username: driver1, driver2, driver3, driver4, driver5');
        $this->command->info('  Password: password (for all)');
        $this->command->info('');
        $this->command->info('Data Summary:');
        $this->command->info('  - 2 Admins');
        $this->command->info('  - 5 Drivers');
        $this->command->info('  - 5 E-Jeeps (4 active, 1 in maintenance)');
        $this->command->info('  - 3 Routes with stops');
        $this->command->info('  - 4 Schedules for today');
        $this->command->info('  - 4 Trips (1 in-progress, 2 completed, 1 scheduled)');
        $this->command->info('  - 4 Notifications');
    }
}
