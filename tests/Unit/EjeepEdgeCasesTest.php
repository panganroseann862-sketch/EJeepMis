<?php

namespace Tests\Unit;

use App\Models\Ejeep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EjeepEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that duplicate vehicle_number is rejected at database level
     * Requirements: 2.1, 12.6
     */
    public function test_duplicate_vehicle_number_rejected(): void
    {
        Ejeep::factory()->create(['vehicle_number' => 'EJ-001']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Ejeep::factory()->create(['vehicle_number' => 'EJ-001']);
    }

    /**
     * Test that duplicate plate_number is rejected at database level
     * Requirements: 2.1, 12.6
     */
    public function test_duplicate_plate_number_rejected(): void
    {
        Ejeep::factory()->create(['plate_number' => 'ABC1234']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Ejeep::factory()->create(['plate_number' => 'ABC1234']);
    }

    /**
     * Test that zero capacity value is rejected
     * Requirements: 12.6
     */
    public function test_zero_capacity_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Ejeep::factory()->create(['passenger_capacity' => 0]);
    }

    /**
     * Test that negative capacity value is rejected
     * Requirements: 12.6
     */
    public function test_negative_capacity_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Ejeep::factory()->create(['passenger_capacity' => -5]);
    }

    /**
     * Test soft delete functionality - deleted E-Jeep is not in active queries
     * Requirements: 2.3
     */
    public function test_soft_deleted_ejeep_not_in_active_queries(): void
    {
        $ejeep = Ejeep::factory()->create(['vehicle_number' => 'EJ-001']);
        $ejeepId = $ejeep->id;

        // Verify E-Jeep exists
        $this->assertDatabaseHas('ejeeps', ['id' => $ejeepId, 'deleted_at' => null]);

        // Soft delete the E-Jeep
        $ejeep->delete();

        // Verify E-Jeep is soft deleted (deleted_at is set)
        $this->assertSoftDeleted('ejeeps', ['id' => $ejeepId]);

        // Verify E-Jeep is not in default queries
        $this->assertNull(Ejeep::find($ejeepId));

        // Verify E-Jeep can be found with trashed
        $this->assertNotNull(Ejeep::withTrashed()->find($ejeepId));
    }

    /**
     * Test soft delete functionality - deleted E-Jeep can be restored
     * Requirements: 2.3
     */
    public function test_soft_deleted_ejeep_can_be_restored(): void
    {
        $ejeep = Ejeep::factory()->create(['vehicle_number' => 'EJ-002']);
        $ejeepId = $ejeep->id;

        // Soft delete
        $ejeep->delete();
        $this->assertSoftDeleted('ejeeps', ['id' => $ejeepId]);

        // Restore
        $ejeep->restore();

        // Verify E-Jeep is back in active queries
        $this->assertDatabaseHas('ejeeps', ['id' => $ejeepId, 'deleted_at' => null]);
        $this->assertNotNull(Ejeep::find($ejeepId));
    }

    /**
     * Test soft delete functionality - force delete permanently removes E-Jeep
     * Requirements: 2.3
     */
    public function test_force_delete_permanently_removes_ejeep(): void
    {
        $ejeep = Ejeep::factory()->create(['vehicle_number' => 'EJ-003']);
        $ejeepId = $ejeep->id;

        // Force delete
        $ejeep->forceDelete();

        // Verify E-Jeep is completely removed from database
        $this->assertDatabaseMissing('ejeeps', ['id' => $ejeepId]);
        $this->assertNull(Ejeep::withTrashed()->find($ejeepId));
    }

    /**
     * Test that soft deleted E-Jeeps are excluded from active scope
     * Requirements: 2.3, 2.7
     */
    public function test_soft_deleted_ejeeps_excluded_from_active_scope(): void
    {
        $activeEjeep = Ejeep::factory()->create(['operational_status' => 'active']);
        $deletedEjeep = Ejeep::factory()->create(['operational_status' => 'active']);
        
        $deletedEjeep->delete();

        $activeEjeeps = Ejeep::active()->get();

        $this->assertCount(1, $activeEjeeps);
        $this->assertTrue($activeEjeeps->contains($activeEjeep));
        $this->assertFalse($activeEjeeps->contains($deletedEjeep));
    }

    /**
     * Test that duplicate vehicle_number is NOT allowed even after soft delete
     * This ensures vehicle numbers remain unique across all records (active and deleted)
     * Requirements: 2.1, 2.3
     */
    public function test_duplicate_vehicle_number_not_allowed_after_soft_delete(): void
    {
        $ejeep = Ejeep::factory()->create(['vehicle_number' => 'EJ-004']);
        $ejeep->delete();

        // Should NOT be able to create new E-Jeep with same vehicle_number after soft delete
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Ejeep::factory()->create(['vehicle_number' => 'EJ-004']);
    }

    /**
     * Test that duplicate plate_number is NOT allowed even after soft delete
     * This ensures plate numbers remain unique across all records (active and deleted)
     * Requirements: 2.1, 2.3
     */
    public function test_duplicate_plate_number_not_allowed_after_soft_delete(): void
    {
        $ejeep = Ejeep::factory()->create(['plate_number' => 'XYZ9999']);
        $ejeep->delete();

        // Should NOT be able to create new E-Jeep with same plate_number after soft delete
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Ejeep::factory()->create(['plate_number' => 'XYZ9999']);
    }

    /**
     * Test that capacity must be a positive integer
     * Requirements: 12.6
     */
    public function test_capacity_must_be_positive_integer(): void
    {
        // Valid positive capacity should work
        $ejeep = Ejeep::factory()->create(['passenger_capacity' => 20]);
        $this->assertEquals(20, $ejeep->passenger_capacity);

        // Test boundary: capacity of 1 should work
        $ejeep2 = Ejeep::factory()->create(['passenger_capacity' => 1]);
        $this->assertEquals(1, $ejeep2->passenger_capacity);

        // Test large capacity should work
        $ejeep3 = Ejeep::factory()->create(['passenger_capacity' => 100]);
        $this->assertEquals(100, $ejeep3->passenger_capacity);
    }
}
