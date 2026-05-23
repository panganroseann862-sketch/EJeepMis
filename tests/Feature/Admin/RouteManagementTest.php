<?php

namespace Tests\Feature\Admin;

use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_routes_index(): void
    {
        $admin = User::factory()->admin()->create();
        Route::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.routes.index'));

        $response->assertOk();
        $response->assertSee('Route Management');
    }

    public function test_admin_can_view_create_route_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.routes.create'));

        $response->assertOk();
        $response->assertSee('Create New Route');
    }

    public function test_admin_can_create_route_with_stops(): void
    {
        $admin = User::factory()->admin()->create();

        $routeData = [
            'route_name' => 'Main Campus Loop',
            'route_code' => 'R001',
            'description' => 'Main campus route',
            'status' => 'active',
            'stops' => [
                [
                    'stop_name' => 'Main Gate',
                    'location_description' => 'Main entrance',
                    'latitude' => 14.5995,
                    'longitude' => 120.9842,
                ],
                [
                    'stop_name' => 'Library',
                    'location_description' => 'Central library',
                    'latitude' => 14.6000,
                    'longitude' => 120.9850,
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('admin.routes.store'), $routeData);

        $response->assertRedirect();
        $this->assertDatabaseHas('routes', [
            'route_code' => 'R001',
            'route_name' => 'Main Campus Loop',
        ]);
        $this->assertDatabaseHas('stops', [
            'stop_name' => 'Main Gate',
            'sequence_order' => 1,
        ]);
        $this->assertDatabaseHas('stops', [
            'stop_name' => 'Library',
            'sequence_order' => 2,
        ]);
    }

    public function test_admin_can_view_route_details(): void
    {
        $admin = User::factory()->admin()->create();
        $route = Route::factory()->create(['route_code' => 'R001']);

        $response = $this->actingAs($admin)->get(route('admin.routes.show', $route));

        $response->assertOk();
        $response->assertSee('R001');
    }

    public function test_admin_can_update_route(): void
    {
        $admin = User::factory()->admin()->create();
        $route = Route::factory()->create(['route_code' => 'R001']);

        $updateData = [
            'route_name' => 'Updated Route Name',
            'route_code' => 'R001',
            'description' => 'Updated description',
            'status' => 'inactive',
        ];

        $response = $this->actingAs($admin)->put(route('admin.routes.update', $route), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('routes', [
            'id' => $route->id,
            'route_name' => 'Updated Route Name',
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_route(): void
    {
        $admin = User::factory()->admin()->create();
        $route = Route::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.routes.destroy', $route));

        $response->assertRedirect();
        $this->assertSoftDeleted('routes', ['id' => $route->id]);
    }

    public function test_driver_cannot_access_route_management(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('admin.routes.index'));

        $response->assertForbidden();
    }

    public function test_route_code_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Route::factory()->create(['route_code' => 'R001']);

        $routeData = [
            'route_name' => 'Another Route',
            'route_code' => 'R001',
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->post(route('admin.routes.store'), $routeData);

        $response->assertSessionHasErrors('route_code');
    }
}
