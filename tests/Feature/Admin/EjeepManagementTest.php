<?php

namespace Tests\Feature\Admin;

use App\Models\Ejeep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EjeepManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_ejeeps_index(): void
    {
        $admin = User::factory()->admin()->create();
        Ejeep::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.ejeeps.index'));

        $response->assertOk();
        $response->assertViewIs('admin.ejeeps.index');
        $response->assertViewHas('ejeeps');
    }

    public function test_admin_can_view_create_ejeep_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.ejeeps.create'));

        $response->assertOk();
        $response->assertViewIs('admin.ejeeps.create');
    }

    public function test_admin_can_create_ejeep(): void
    {
        $admin = User::factory()->admin()->create();

        $ejeepData = [
            'vehicle_number' => 'EJ-001',
            'plate_number' => 'ABC1234',
            'passenger_capacity' => 20,
            'operational_status' => 'active',
        ];

        $response = $this->actingAs($admin)->post(route('admin.ejeeps.store'), $ejeepData);

        $response->assertRedirect(route('admin.ejeeps.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ejeeps', $ejeepData);
    }

    public function test_admin_can_view_ejeep_details(): void
    {
        $admin = User::factory()->admin()->create();
        $ejeep = Ejeep::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.ejeeps.show', $ejeep));

        $response->assertOk();
        $response->assertViewIs('admin.ejeeps.show');
        $response->assertViewHas('ejeep', $ejeep);
        $response->assertSee($ejeep->vehicle_number);
    }

    public function test_admin_can_view_edit_ejeep_form(): void
    {
        $admin = User::factory()->admin()->create();
        $ejeep = Ejeep::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.ejeeps.edit', $ejeep));

        $response->assertOk();
        $response->assertViewIs('admin.ejeeps.edit');
        $response->assertViewHas('ejeep', $ejeep);
    }

    public function test_admin_can_update_ejeep(): void
    {
        $admin = User::factory()->admin()->create();
        $ejeep = Ejeep::factory()->create();

        $updatedData = [
            'vehicle_number' => 'EJ-999',
            'plate_number' => 'XYZ9999',
            'passenger_capacity' => 25,
            'operational_status' => 'maintenance',
        ];

        $response = $this->actingAs($admin)->put(route('admin.ejeeps.update', $ejeep), $updatedData);

        $response->assertRedirect(route('admin.ejeeps.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ejeeps', $updatedData);
    }

    public function test_admin_can_delete_ejeep(): void
    {
        $admin = User::factory()->admin()->create();
        $ejeep = Ejeep::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.ejeeps.destroy', $ejeep));

        $response->assertRedirect(route('admin.ejeeps.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('ejeeps', ['id' => $ejeep->id]);
    }

    public function test_driver_cannot_access_ejeep_management(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('admin.ejeeps.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_ejeep_management(): void
    {
        $response = $this->get(route('admin.ejeeps.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_validation_rejects_duplicate_vehicle_number(): void
    {
        $admin = User::factory()->admin()->create();
        $existingEjeep = Ejeep::factory()->create(['vehicle_number' => 'EJ-001']);

        $response = $this->actingAs($admin)->post(route('admin.ejeeps.store'), [
            'vehicle_number' => 'EJ-001',
            'plate_number' => 'ABC1234',
            'passenger_capacity' => 20,
            'operational_status' => 'active',
        ]);

        $response->assertSessionHasErrors('vehicle_number');
    }

    public function test_validation_rejects_duplicate_plate_number(): void
    {
        $admin = User::factory()->admin()->create();
        $existingEjeep = Ejeep::factory()->create(['plate_number' => 'ABC1234']);

        $response = $this->actingAs($admin)->post(route('admin.ejeeps.store'), [
            'vehicle_number' => 'EJ-002',
            'plate_number' => 'ABC1234',
            'passenger_capacity' => 20,
            'operational_status' => 'active',
        ]);

        $response->assertSessionHasErrors('plate_number');
    }

    public function test_validation_rejects_invalid_capacity(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.ejeeps.store'), [
            'vehicle_number' => 'EJ-001',
            'plate_number' => 'ABC1234',
            'passenger_capacity' => 0,
            'operational_status' => 'active',
        ]);

        $response->assertSessionHasErrors('passenger_capacity');
    }
}
