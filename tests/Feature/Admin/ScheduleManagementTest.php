<?php

namespace Tests\Feature\Admin;

use App\Models\Ejeep;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_schedules_index(): void
    {
        $admin = User::factory()->admin()->create();
        Schedule::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.schedules.index'));

        $response->assertOk();
        $response->assertSee('Schedule Management');
    }

    public function test_admin_can_view_create_schedule_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.schedules.create'));

        $response->assertOk();
        $response->assertSee('Create New Schedule');
    }

    public function test_admin_can_create_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $route = Route::factory()->create();
        $ejeep = Ejeep::factory()->create(['operational_status' => 'active']);
        $driver = User::factory()->driver()->create();

        $scheduleData = [
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'departure_time' => '08:00',
            'day_of_week' => 'monday',
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->post(route('admin.schedules.store'), $scheduleData);

        $response->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'route_id' => $route->id,
            'ejeep_id' => $ejeep->id,
            'driver_id' => $driver->id,
            'day_of_week' => 'monday',
        ]);
    }

    public function test_admin_can_view_schedule_details(): void
    {
        $admin = User::factory()->admin()->create();
        $schedule = Schedule::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.schedules.show', $schedule));

        $response->assertOk();
        $response->assertSee('Schedule Details');
    }

    public function test_admin_can_update_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $schedule = Schedule::factory()->create(['day_of_week' => 'monday']);

        $updateData = [
            'route_id' => $schedule->route_id,
            'ejeep_id' => $schedule->ejeep_id,
            'driver_id' => $schedule->driver_id,
            'departure_time' => '09:00',
            'day_of_week' => 'tuesday',
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->put(route('admin.schedules.update', $schedule), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'day_of_week' => 'tuesday',
        ]);
    }

    public function test_admin_can_delete_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $schedule = Schedule::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.schedules.destroy', $schedule));

        $response->assertRedirect();
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_maintenance_ejeeps_are_excluded_from_schedule_creation(): void
    {
        $admin = User::factory()->admin()->create();
        $route = Route::factory()->create();
        $maintenanceEjeep = Ejeep::factory()->create(['operational_status' => 'maintenance']);
        $driver = User::factory()->driver()->create();

        $scheduleData = [
            'route_id' => $route->id,
            'ejeep_id' => $maintenanceEjeep->id,
            'driver_id' => $driver->id,
            'departure_time' => '08:00',
            'day_of_week' => 'monday',
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->post(route('admin.schedules.store'), $scheduleData);

        $response->assertSessionHasErrors('ejeep_id');
    }

    public function test_driver_cannot_access_schedule_management(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('admin.schedules.index'));

        $response->assertForbidden();
    }

    public function test_schedule_requires_valid_foreign_keys(): void
    {
        $admin = User::factory()->admin()->create();

        $scheduleData = [
            'route_id' => 99999,
            'ejeep_id' => 99999,
            'driver_id' => 99999,
            'departure_time' => '08:00',
            'day_of_week' => 'monday',
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->post(route('admin.schedules.store'), $scheduleData);

        $response->assertSessionHasErrors(['route_id', 'ejeep_id', 'driver_id']);
    }
}
