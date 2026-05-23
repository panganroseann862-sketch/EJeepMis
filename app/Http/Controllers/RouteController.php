<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouteRequest;
use App\Http\Requests\UpdateRouteRequest;
use App\Models\Route;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RouteController extends Controller
{
    /**
     * Display a listing of routes.
     */
    public function index(): View
    {
        $routes = Route::with(['stops' => function ($query) {
                $query->orderBy('sequence_order');
            }])
            ->withCount('stops')
            ->withCount(['schedules' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('route_code')
            ->paginate(15);

        return view('admin.routes.index', compact('routes'));
    }

    /**
     * Show the form for creating a new route.
     */
    public function create(): View
    {
        return view('admin.routes.create');
    }

    /**
     * Store a newly created route in storage.
     */
    public function store(StoreRouteRequest $request): RedirectResponse
    {
        try {
            $route = Route::create($request->validated());

            // Create stops if provided
            if ($request->has('stops')) {
                foreach ($request->stops as $index => $stopData) {
                    $route->stops()->create([
                        'stop_name' => $stopData['stop_name'],
                        'location_description' => $stopData['location_description'] ?? null,
                        'latitude' => $stopData['latitude'] ?? null,
                        'longitude' => $stopData['longitude'] ?? null,
                        'sequence_order' => $index + 1,
                    ]);
                }
            }

            return redirect()
                ->route('admin.routes.show', $route)
                ->with('success', 'Route created successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to create route', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create route. Please try again.');
        }
    }

    /**
     * Display the specified route.
     */
    public function show(Route $route): View
    {
        $route->load(['stops' => function ($query) {
            $query->orderBy('sequence_order');
        }]);

        return view('admin.routes.show', compact('route'));
    }

    /**
     * Show the form for editing the specified route.
     */
    public function edit(Route $route): View
    {
        $route->load(['stops' => function ($query) {
            $query->orderBy('sequence_order');
        }]);

        return view('admin.routes.edit', compact('route'));
    }

    /**
     * Update the specified route in storage.
     */
    public function update(UpdateRouteRequest $request, Route $route): RedirectResponse
    {
        try {
            $route->update($request->validated());

            // Update stops if provided
            if ($request->has('stops')) {
                // Delete existing stops
                $route->stops()->delete();

                // Create new stops
                foreach ($request->stops as $index => $stopData) {
                    $route->stops()->create([
                        'stop_name' => $stopData['stop_name'],
                        'location_description' => $stopData['location_description'] ?? null,
                        'latitude' => $stopData['latitude'] ?? null,
                        'longitude' => $stopData['longitude'] ?? null,
                        'sequence_order' => $index + 1,
                    ]);
                }
            }

            // Notify all drivers assigned to this route
            $this->notifyDriversOfRouteUpdate($route);

            return redirect()
                ->route('admin.routes.show', $route)
                ->with('success', 'Route updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to update route', [
                'route_id' => $route->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update route. Please try again.');
        }
    }

    /**
     * Notify all drivers assigned to schedules using this route.
     */
    private function notifyDriversOfRouteUpdate(Route $route): void
    {
        $notificationService = app(\App\Services\NotificationService::class);

        // Get all unique drivers assigned to schedules for this route
        $drivers = \App\Models\User::whereIn('id', function ($query) use ($route) {
            $query->select('driver_id')
                ->from('schedules')
                ->where('route_id', $route->id)
                ->where('status', 'active')
                ->distinct();
        })->get();

        foreach ($drivers as $driver) {
            $notificationService->notifyRouteUpdate($driver, $route);
        }
    }


    /**
     * Remove the specified route from storage.
     */
    public function destroy(Route $route): RedirectResponse
    {
        try {
            // Check if route has active schedules
            $activeSchedules = $route->schedules()->where('status', 'active')->count();
            
            if ($activeSchedules > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete route with active schedules.');
            }
            
            $route->delete();

            return redirect()
                ->route('admin.routes.index')
                ->with('success', 'Route deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to delete route', [
                'route_id' => $route->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to delete route. Please try again.');
        }
    }
}
