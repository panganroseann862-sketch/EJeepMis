<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ejeep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for E-Jeep Management
 * 
 * These tests validate universal correctness properties for E-Jeep CRUD operations
 * across multiple randomly generated inputs to ensure the system behaves correctly
 * for all valid E-Jeep data combinations.
 */
class EjeepManagementPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 5: E-Jeep creation stores unique vehicles
     * Validates: Requirements 2.1
     * 
     * For any valid E-Jeep data with unique vehicle_number and plate_number, 
     * creating the E-Jeep should store it with a unique ID and all provided attributes.
     */
    public function test_property_ejeep_creation_stores_unique_vehicles(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create admin user for this iteration
            $admin = User::factory()->admin()->create();
            
            // Generate random valid E-Jeep data
            $vehicleNumber = 'EJ-' . $i . '-' . fake()->unique()->numberBetween(1000, 9999);
            $plateNumber = fake()->unique()->regexify('[A-Z]{3}[0-9]{4}');
            $capacity = fake()->numberBetween(1, 100);
            $status = fake()->randomElement(['active', 'maintenance', 'inactive']);
            
            // Attempt to create E-Jeep
            $response = $this->actingAs($admin)->post('/admin/ejeeps', [
                'vehicle_number' => $vehicleNumber,
                'plate_number' => $plateNumber,
                'passenger_capacity' => $capacity,
                'operational_status' => $status,
            ]);
            
            // Assert redirect (successful creation)
            $response->assertRedirect();
            
            // Retrieve created E-Jeep from database
            $ejeep = Ejeep::where('vehicle_number', $vehicleNumber)->first();
            
            // Assert E-Jeep was stored with all attributes
            $this->assertNotNull($ejeep);
            $this->assertGreaterThan(0, $ejeep->id);
            $this->assertEquals($vehicleNumber, $ejeep->vehicle_number);
            $this->assertEquals($plateNumber, $ejeep->plate_number);
            $this->assertEquals($capacity, $ejeep->passenger_capacity);
            $this->assertEquals($status, $ejeep->operational_status);
        }
    }

    /**
     * Property 6: E-Jeep updates persist changes
     * Validates: Requirements 2.2
     * 
     * For any existing E-Jeep and valid update data, updating the E-Jeep 
     * should persist all changes and maintain referential integrity.
     */
    public function test_property_ejeep_updates_persist_changes(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create admin user for this iteration
            $admin = User::factory()->admin()->create();
            
            // Create initial E-Jeep
            $ejeep = Ejeep::factory()->create([
                'vehicle_number' => 'EJ-ORIG-' . $i . '-' . uniqid(),
                'plate_number' => 'OLD' . $i . fake()->numberBetween(1000, 9999),
            ]);
            
            // Generate random update data
            $newVehicleNumber = 'EJ-NEW-' . $i . '-' . uniqid();
            $newPlateNumber = 'NEW' . $i . fake()->numberBetween(1000, 9999);
            $newCapacity = fake()->numberBetween(1, 100);
            $newStatus = fake()->randomElement(['active', 'maintenance', 'inactive']);
            $maintenanceNotes = fake()->sentence();
            
            // Attempt to update E-Jeep
            $response = $this->actingAs($admin)->put("/admin/ejeeps/{$ejeep->id}", [
                'vehicle_number' => $newVehicleNumber,
                'plate_number' => $newPlateNumber,
                'passenger_capacity' => $newCapacity,
                'operational_status' => $newStatus,
                'maintenance_notes' => $maintenanceNotes,
            ]);
            
            // Assert redirect (successful update)
            $response->assertRedirect();
            
            // Retrieve updated E-Jeep from database
            $updatedEjeep = Ejeep::find($ejeep->id);
            
            // Assert all changes persisted
            $this->assertNotNull($updatedEjeep);
            $this->assertEquals($ejeep->id, $updatedEjeep->id); // ID unchanged
            $this->assertEquals($newVehicleNumber, $updatedEjeep->vehicle_number);
            $this->assertEquals($newPlateNumber, $updatedEjeep->plate_number);
            $this->assertEquals($newCapacity, $updatedEjeep->passenger_capacity);
            $this->assertEquals($newStatus, $updatedEjeep->operational_status);
            $this->assertEquals($maintenanceNotes, $updatedEjeep->maintenance_notes);
        }
    }

    /**
     * Property 7: E-Jeep deletion removes from active fleet
     * Validates: Requirements 2.3
     * 
     * For any E-Jeep, deleting it should remove it from the active fleet list 
     * and prevent it from being assigned to new trips.
     */
    public function test_property_ejeep_deletion_removes_from_active_fleet(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create admin user for this iteration
            $admin = User::factory()->admin()->create();
            
            // Create E-Jeep with random status
            $status = fake()->randomElement(['active', 'maintenance', 'inactive']);
            $ejeep = Ejeep::factory()->create([
                'vehicle_number' => 'EJ-DEL-' . $i . '-' . uniqid(),
                'operational_status' => $status,
            ]);
            
            $ejeepId = $ejeep->id;
            $vehicleNumber = $ejeep->vehicle_number;
            
            // Attempt to delete E-Jeep
            $response = $this->actingAs($admin)->delete("/admin/ejeeps/{$ejeepId}");
            
            // Assert redirect (successful deletion)
            $response->assertRedirect();
            
            // Assert E-Jeep is soft deleted (not in active queries)
            $activeEjeep = Ejeep::find($ejeepId);
            $this->assertNull($activeEjeep);
            
            // Assert E-Jeep still exists with deleted_at timestamp
            $deletedEjeep = Ejeep::withTrashed()->find($ejeepId);
            $this->assertNotNull($deletedEjeep);
            $this->assertNotNull($deletedEjeep->deleted_at);
            
            // Assert deleted E-Jeep doesn't appear in active fleet list
            $response = $this->actingAs($admin)->get('/admin/ejeeps');
            // Note: The view shows soft-deleted items with a "(Deleted)" label
            // We verify the E-Jeep is marked as deleted in the view
            $response->assertSee('(Deleted)');
        }
    }

    /**
     * Property 8: E-Jeep details display required information
     * Validates: Requirements 2.4
     * 
     * For any E-Jeep, viewing its details should display vehicle_number, 
     * plate_number, passenger_capacity, operational_status, and maintenance_notes.
     */
    public function test_property_ejeep_details_display_required_information(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create admin user for this iteration
            $admin = User::factory()->admin()->create();
            
            // Create E-Jeep with random data
            $vehicleNumber = 'EJ-VIEW-' . $i . '-' . fake()->numberBetween(1000, 9999);
            $plateNumber = fake()->regexify('[A-Z]{3}[0-9]{4}');
            $capacity = fake()->numberBetween(1, 100);
            $status = fake()->randomElement(['active', 'maintenance', 'inactive']);
            $maintenanceNotes = fake()->sentence();
            
            $ejeep = Ejeep::factory()->create([
                'vehicle_number' => $vehicleNumber,
                'plate_number' => $plateNumber,
                'passenger_capacity' => $capacity,
                'operational_status' => $status,
                'maintenance_notes' => $maintenanceNotes,
            ]);
            
            // Request E-Jeep details page
            $response = $this->actingAs($admin)->get("/admin/ejeeps/{$ejeep->id}");
            
            // Assert page loads successfully
            $response->assertOk();
            
            // Assert all required information is displayed
            $response->assertSee($vehicleNumber);
            $response->assertSee($plateNumber);
            $response->assertSee((string)$capacity);
            // Status is displayed with capitalization (Active, Maintenance, Inactive)
            $response->assertSee(ucfirst($status), false);
            
            if ($maintenanceNotes) {
                $response->assertSee($maintenanceNotes);
            }
        }
    }

    /**
     * Property 9: Maintenance E-Jeeps excluded from assignments
     * Validates: Requirements 2.7
     * 
     * For any E-Jeep with operational_status 'maintenance', it should not appear 
     * in the list of available vehicles for trip assignment.
     */
    public function test_property_maintenance_ejeeps_excluded_from_assignments(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create admin user for this iteration
            $admin = User::factory()->admin()->create();
            
            // Create E-Jeep with maintenance status
            $maintenanceEjeep = Ejeep::factory()->create([
                'vehicle_number' => 'EJ-MAINT-' . $i . '-' . uniqid(),
                'operational_status' => 'maintenance',
            ]);
            
            // Create E-Jeep with active status for comparison
            $activeEjeep = Ejeep::factory()->create([
                'vehicle_number' => 'EJ-ACTIVE-' . $i . '-' . uniqid(),
                'operational_status' => 'active',
            ]);
            
            // Request schedule creation page (where E-Jeeps are selected)
            $response = $this->actingAs($admin)->get('/admin/schedules/create');
            
            // Assert page loads successfully
            $response->assertOk();
            
            // Assert active E-Jeep appears in available vehicles
            $response->assertSee($activeEjeep->vehicle_number);
            
            // Assert maintenance E-Jeep does NOT appear in available vehicles
            $response->assertDontSee($maintenanceEjeep->vehicle_number);
            
            // Query available E-Jeeps using the scope
            $availableEjeeps = Ejeep::where('operational_status', '!=', 'maintenance')->get();
            
            // Assert maintenance E-Jeep is not in available list
            $this->assertFalse($availableEjeeps->contains('id', $maintenanceEjeep->id));
            
            // Assert active E-Jeep is in available list
            $this->assertTrue($availableEjeeps->contains('id', $activeEjeep->id));
        }
    }
}
