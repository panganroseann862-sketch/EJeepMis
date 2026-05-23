<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Trip;
use App\Models\Schedule;
use App\Models\Route;
use App\Models\Ejeep;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for Driver Management
 * 
 * These tests validate universal correctness properties for driver
 * management operations including creation, updates, deletion, and
 * performance metrics display.
 * 
 * Note: These tests manually manage database state to support property-based
 * testing with 100+ iterations per test method.
 */
class DriverManagementPropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for each test method
        Artisan::call('migrate:fresh');
    }
    
    protected function tearDown(): void
    {
        // Clean up database after each test
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('users')->truncate();
        DB::table('trips')->truncate();
        DB::table('schedules')->truncate();
        DB::table('routes')->truncate();
        DB::table('ejeeps')->truncate();
        DB::statement('PRAGMA foreign_keys = ON');
        
        parent::tearDown();
    }

    /**
     * Property 10: Driver creation stores credentials
     * Validates: Requirements 3.1
     * 
     * For any valid driver data with unique username and email, creating 
     * the driver should store all information including hashed authentication 
     * credentials.
     */
    public function test_property_driver_creation_stores_credentials(): void
    {
        // Create admin user once for all iterations
        $admin = User::factory()->admin()->create();
        
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Generate random valid driver data
            $username = 'driver_' . $i . '_' . uniqid();
            $email = 'driver' . $i . '_' . uniqid() . '@example.com';
            $password = fake()->password(8, 20);
            $firstName = fake()->firstName();
            $lastName = fake()->lastName();
            $phone = fake()->optional()->numerify('##########');
            
            // Create driver via controller
            $response = $this->actingAs($admin)->post(route('admin.drivers.store'), [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ]);
            
            // Assert redirect (successful creation)
            $response->assertRedirect();
            
            // Retrieve created driver from database
            $driver = User::where('username', $username)->first();
            
            // Assert driver was created with all attributes
            $this->assertNotNull($driver);
            $this->assertEquals($username, $driver->username);
            $this->assertEquals($email, $driver->email);
            $this->assertEquals($firstName, $driver->first_name);
            $this->assertEquals($lastName, $driver->last_name);
            $this->assertEquals($phone, $driver->phone);
            $this->assertEquals('driver', $driver->role);
            $this->assertEquals('active', $driver->status);
            
            // Assert password is hashed (not plaintext)
            $this->assertNotEquals($password, $driver->password);
            $this->assertTrue(Hash::check($password, $driver->password));
            
            // Assert driver has unique ID
            $this->assertGreaterThan(0, $driver->id);
            
            // Clean up for next iteration
            DB::table('users')->where('id', $driver->id)->delete();
        }
        
        // Clean up admin
        DB::table('users')->where('id', $admin->id)->delete();
    }

    /**
     * Property 11: Driver updates persist immediately
     * Validates: Requirements 3.2
     * 
     * For any existing driver and valid update data, updating the driver 
     * should persist all changes immediately and maintain data integrity.
     */
    public function test_property_driver_updates_persist_immediately(): void
    {
        // Create admin user once for all iterations
        $admin = User::factory()->admin()->create();
        
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create initial driver
            $driver = User::factory()->driver()->create([
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'original' . $i . '_' . uniqid() . '@example.com',
            ]);
            
            // Generate random update data
            $newEmail = 'updated' . $i . '_' . uniqid() . '@example.com';
            $newFirstName = fake()->firstName();
            $newLastName = fake()->lastName();
            $newPhone = fake()->optional()->numerify('##########');
            $newStatus = fake()->randomElement(['active', 'inactive']);
            
            // Update driver via controller
            $response = $this->actingAs($admin)->put(route('admin.drivers.update', $driver), [
                'username' => $driver->username, // Username typically not changed
                'email' => $newEmail,
                'first_name' => $newFirstName,
                'last_name' => $newLastName,
                'phone' => $newPhone,
                'status' => $newStatus,
            ]);
            
            // Assert redirect (successful update)
            $response->assertRedirect();
            
            // Retrieve driver immediately after update
            $updatedDriver = User::find($driver->id);
            
            // Assert all changes persisted immediately
            $this->assertNotNull($updatedDriver);
            $this->assertEquals($newEmail, $updatedDriver->email);
            $this->assertEquals($newFirstName, $updatedDriver->first_name);
            $this->assertEquals($newLastName, $updatedDriver->last_name);
            $this->assertEquals($newPhone, $updatedDriver->phone);
            $this->assertEquals($newStatus, $updatedDriver->status);
            
            // Assert data integrity maintained
            $this->assertEquals($driver->id, $updatedDriver->id);
            $this->assertEquals($driver->username, $updatedDriver->username);
            $this->assertEquals('driver', $updatedDriver->role);
            
            // Clean up for next iteration
            DB::table('users')->where('id', $driver->id)->delete();
        }
        
        // Clean up admin
        DB::table('users')->where('id', $admin->id)->delete();
    }

    /**
     * Property 12: Driver deletion removes from system
     * Validates: Requirements 3.3
     * 
     * For any driver, deleting them should remove their account and 
     * prevent future authentication.
     */
    public function test_property_driver_deletion_removes_from_system(): void
    {
        // Create admin user once for all iterations
        $admin = User::factory()->admin()->create();
        
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create driver with random data
            $username = 'driver_' . $i . '_' . uniqid();
            $password = fake()->password(8, 20);
            
            $driver = User::factory()->driver()->create([
                'username' => $username,
                'email' => 'driver' . $i . '_' . uniqid() . '@example.com',
                'password' => Hash::make($password),
            ]);
            
            $driverId = $driver->id;
            
            // Delete driver via controller
            $response = $this->actingAs($admin)->delete(route('admin.drivers.destroy', $driver));
            
            // Assert redirect (successful deletion)
            $response->assertRedirect();
            
            // Logout admin to clear authentication state
            $this->post('/logout');
            
            // Assert driver no longer exists in database
            $deletedDriver = User::find($driverId);
            $this->assertNull($deletedDriver);
            
            // Assert authentication with deleted driver credentials fails
            $authResponse = $this->post('/login', [
                'username' => $username,
                'password' => $password,
            ]);
            
            $this->assertGuest();
            $authResponse->assertSessionHasErrors();
        }
        
        // Clean up admin
        DB::table('users')->where('id', $admin->id)->delete();
    }

    /**
     * Property 14: Driver details display complete information
     * Validates: Requirements 3.6
     * 
     * For any driver, viewing their details should display assigned routes, 
     * current status, completed trip count, and schedule adherence rate.
     */
    public function test_property_driver_details_display_complete_information(): void
    {
        // Create admin user once for all iterations
        $admin = User::factory()->admin()->create();
        
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create driver with random data
            $driver = User::factory()->driver()->create([
                'username' => 'driver_' . $i . '_' . uniqid(),
                'email' => 'driver' . $i . '_' . uniqid() . '@example.com',
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'status' => fake()->randomElement(['active', 'inactive']),
            ]);
            
            // Create some trips for the driver to generate performance data
            $completedTripsCount = fake()->numberBetween(0, 10);
            
            if ($completedTripsCount > 0) {
                $route = Route::factory()->create();
                $ejeep = Ejeep::factory()->create();
                $schedule = Schedule::factory()->create([
                    'driver_id' => $driver->id,
                    'route_id' => $route->id,
                    'ejeep_id' => $ejeep->id,
                ]);
                
                for ($j = 0; $j < $completedTripsCount; $j++) {
                    Trip::factory()->create([
                        'driver_id' => $driver->id,
                        'schedule_id' => $schedule->id,
                        'route_id' => $route->id,
                        'ejeep_id' => $ejeep->id,
                        'status' => 'completed',
                    ]);
                }
            }
            
            // View driver details via controller
            $response = $this->actingAs($admin)->get(route('admin.drivers.show', $driver));
            
            // Assert page loads successfully
            $response->assertOk();
            
            // Assert driver information is displayed
            $response->assertSee($driver->first_name);
            $response->assertSee($driver->last_name);
            $response->assertSee($driver->email);
            $response->assertSee($driver->username);
            
            // Assert status is displayed (case-insensitive check)
            $response->assertSeeText(ucfirst($driver->status));
            
            // Assert performance metrics section is present
            $response->assertSee('Performance Metrics');
            $response->assertSee('Completed Trips');
            $response->assertSee('Schedule Adherence');
            
            // Assert completed trips count is displayed
            $response->assertSee((string)$completedTripsCount);
            
            // Clean up for next iteration
            if ($completedTripsCount > 0) {
                DB::table('trips')->where('driver_id', $driver->id)->delete();
                DB::table('schedules')->where('id', $schedule->id)->delete();
                DB::table('routes')->where('id', $route->id)->delete();
                DB::table('ejeeps')->where('id', $ejeep->id)->delete();
            }
            DB::table('users')->where('id', $driver->id)->delete();
        }
        
        // Clean up admin
        DB::table('users')->where('id', $admin->id)->delete();
    }
}
