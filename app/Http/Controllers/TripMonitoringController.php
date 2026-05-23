<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripMonitoringController extends Controller
{
    /**
     * Display all trips with filters
     */
    public function index(Request $request): View
    {
        $query = Trip::with(['ejeep', 'driver', 'route', 'schedule']);

        // Filter by status if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by date if provided
        if ($request->has('date') && $request->date !== '') {
            $query->whereDate('scheduled_start_time', $request->date);
        }

        // Filter by driver if provided
        if ($request->has('driver_id') && $request->driver_id !== '') {
            $query->where('driver_id', $request->driver_id);
        }

        // Filter by route if provided
        if ($request->has('route_id') && $request->route_id !== '') {
            $query->where('route_id', $request->route_id);
        }

        $trips = $query->orderBy('scheduled_start_time', 'desc')->paginate(20);
        
        // Eager load drivers and routes for filter dropdowns
        $drivers = User::drivers()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $routes = \App\Models\Route::active()->orderBy('route_code')->get(['id', 'route_name', 'route_code']);

        return view('admin.trips.index', compact('trips', 'drivers', 'routes'));
    }

    /**
     * Display detailed trip view
     */
    public function show(Trip $trip): View
    {
        $trip->load([
            'ejeep',
            'driver',
            'route.stops' => function ($query) {
                $query->orderBy('sequence_order');
            },
            'schedule',
            'passengerLogs.stop' => function ($query) {
                $query->orderBy('recorded_at');
            }
        ]);

        // Calculate next scheduled stop based on passenger log history
        $nextStop = $this->calculateNextStop($trip);

        // Calculate ETA (simplified - assumes 5 minutes per stop)
        $eta = $this->calculateETA($trip, $nextStop);

        return view('admin.trips.show', compact('trip', 'nextStop', 'eta'));
    }

    /**
     * Get active trips for real-time updates
     */
    public function getActiveTrips(): JsonResponse
    {
        try {
            $activeTrips = Trip::with(['ejeep', 'driver', 'route'])
                ->whereIn('status', ['in_progress', 'paused'])
                ->get()
                ->map(function ($trip) {
                    return [
                        'id' => $trip->id,
                        'ejeep' => $trip->ejeep->vehicle_number,
                        'driver' => $trip->driver->first_name . ' ' . $trip->driver->last_name,
                        'route' => $trip->route->route_name,
                        'status' => $trip->status,
                        'current_passenger_count' => $trip->current_passenger_count,
                        'capacity' => $trip->ejeep->passenger_capacity,
                        'is_over_capacity' => $trip->isOverCapacity(),
                        'has_route_deviation' => $trip->has_route_deviation,
                    ];
                });

            return response()->json($activeTrips);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch active trips', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Failed to fetch active trips'], 500);
        }
    }

    /**
     * Get capacity alerts for real-time updates
     */
    public function getCapacityAlerts(): JsonResponse
    {
        try {
            $capacityAlerts = Trip::with(['ejeep', 'driver', 'route'])
                ->whereIn('status', ['in_progress', 'paused'])
                ->whereRaw('current_passenger_count >= (SELECT passenger_capacity FROM ejeeps WHERE ejeeps.id = trips.ejeep_id)')
                ->get()
                ->map(function ($trip) {
                    return [
                        'id' => $trip->id,
                        'ejeep' => $trip->ejeep->vehicle_number,
                        'driver' => $trip->driver->first_name . ' ' . $trip->driver->last_name,
                        'route' => $trip->route->route_name,
                        'current_passenger_count' => $trip->current_passenger_count,
                        'capacity' => $trip->ejeep->passenger_capacity,
                        'overage' => $trip->current_passenger_count - $trip->ejeep->passenger_capacity,
                    ];
                });

            return response()->json($capacityAlerts);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch capacity alerts', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Failed to fetch capacity alerts'], 500);
        }
    }

    /**
     * Calculate next scheduled stop based on passenger log history
     */
    private function calculateNextStop(Trip $trip): ?object
    {
        $orderedStops = $trip->route->stops()->orderBy('sequence_order')->get();
        $visitedStopIds = $trip->passengerLogs()->pluck('stop_id')->unique();

        foreach ($orderedStops as $stop) {
            if (!$visitedStopIds->contains($stop->id)) {
                return $stop;
            }
        }

        return null; // All stops visited
    }

    /**
     * Calculate ETA (simplified - assumes 5 minutes per remaining stop)
     */
    private function calculateETA(Trip $trip, ?object $nextStop): ?string
    {
        if (!$nextStop || $trip->status !== 'in_progress') {
            return null;
        }

        $orderedStops = $trip->route->stops()->orderBy('sequence_order')->get();
        $visitedStopIds = $trip->passengerLogs()->pluck('stop_id')->unique();

        $remainingStops = $orderedStops->filter(function ($stop) use ($visitedStopIds) {
            return !$visitedStopIds->contains($stop->id);
        })->count();

        $minutesRemaining = $remainingStops * 5;

        return now()->addMinutes($minutesRemaining)->format('h:i A');
    }
}
