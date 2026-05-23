<?php

namespace App\Http\Controllers;

use App\Models\Ejeep;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        // Get fleet statistics
        $activeEjeeps = Ejeep::where('operational_status', 'active')->count();
        $maintenanceEjeeps = Ejeep::where('operational_status', 'maintenance')->count();
        $totalEjeeps = Ejeep::count();

        // Get driver statistics
        $totalDrivers = User::where('role', 'driver')->where('status', 'active')->count();
        $driversOnTrip = User::where('role', 'driver')
            ->whereHas('driverTrips', function ($query) {
                $query->where('status', 'in_progress');
            })
            ->count();

        // Get trip statistics
        $ongoingTrips = Trip::where('status', 'in_progress')->count();
        $scheduledTrips = Trip::where('status', 'scheduled')->count();
        $completedToday = Trip::where('status', 'completed')
            ->whereDate('actual_end_time', today())
            ->count();

        // Get active trips with details
        $activeTrips = Trip::with(['ejeep', 'driver', 'route'])
            ->where('status', 'in_progress')
            ->orderBy('actual_start_time', 'desc')
            ->get();

        // Get capacity alerts (trips at or over capacity)
        $capacityAlerts = Trip::with(['ejeep', 'driver', 'route'])
            ->where('status', 'in_progress')
            ->get()
            ->filter(function ($trip) {
                return $trip->current_passenger_count >= $trip->ejeep->passenger_capacity;
            });

        // Get route deviations
        $routeDeviations = Trip::with(['ejeep', 'driver', 'route'])
            ->where('has_route_deviation', true)
            ->whereIn('status', ['in_progress', 'completed'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent completed trips
        $recentTrips = Trip::with(['ejeep', 'driver', 'route'])
            ->where('status', 'completed')
            ->orderBy('actual_end_time', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'activeEjeeps',
            'maintenanceEjeeps',
            'totalEjeeps',
            'totalDrivers',
            'driversOnTrip',
            'ongoingTrips',
            'scheduledTrips',
            'completedToday',
            'activeTrips',
            'capacityAlerts',
            'routeDeviations',
            'recentTrips'
        ));
    }

    /**
     * Get real-time dashboard data for AJAX polling.
     */
    public function getRealtimeData(): JsonResponse
    {
        try {
            // Get fleet statistics
            $activeEjeeps = Ejeep::where('operational_status', 'active')->count();
            
            // Get driver statistics
            $driversOnTrip = User::where('role', 'driver')
                ->whereHas('driverTrips', function ($query) {
                    $query->where('status', 'in_progress');
                })
                ->count();

            // Get trip statistics
            $ongoingTrips = Trip::where('status', 'in_progress')->count();

            // Get capacity alerts (trips at or over capacity)
            $capacityAlerts = Trip::with(['ejeep', 'driver', 'route'])
                ->where('status', 'in_progress')
                ->get()
                ->filter(function ($trip) {
                    return $trip->current_passenger_count >= $trip->ejeep->passenger_capacity;
                })
                ->map(function ($trip) {
                    return [
                        'id' => $trip->id,
                        'vehicle_number' => $trip->ejeep->vehicle_number,
                        'route_name' => $trip->route->route_name,
                        'driver_name' => $trip->driver->first_name . ' ' . $trip->driver->last_name,
                        'current_passenger_count' => $trip->current_passenger_count,
                        'passenger_capacity' => $trip->ejeep->passenger_capacity,
                        'is_over_capacity' => $trip->current_passenger_count > $trip->ejeep->passenger_capacity,
                    ];
                })
                ->values();

            // Get route deviations
            $routeDeviations = Trip::with(['ejeep', 'driver', 'route'])
                ->where('has_route_deviation', true)
                ->whereIn('status', ['in_progress', 'completed'])
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($trip) {
                    return [
                        'id' => $trip->id,
                        'vehicle_number' => $trip->ejeep->vehicle_number,
                        'route_name' => $trip->route->route_name,
                        'driver_name' => $trip->driver->first_name . ' ' . $trip->driver->last_name,
                        'deviation_notes' => $trip->deviation_notes,
                        'status' => $trip->status,
                    ];
                });

            return response()->json([
                'activeEjeeps' => $activeEjeeps,
                'driversOnTrip' => $driversOnTrip,
                'ongoingTrips' => $ongoingTrips,
                'capacityAlerts' => $capacityAlerts,
                'routeDeviations' => $routeDeviations,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch realtime dashboard data', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Failed to fetch dashboard data'], 500);
        }
    }
}
