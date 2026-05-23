<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DriverDashboardController extends Controller
{
    /**
     * Display the driver dashboard.
     */
    public function index(): View
    {
        $driver = Auth::user();
        
        // Get current day of week
        $currentDay = strtolower(now()->format('l'));
        
        // Fetch driver's schedules for current day
        $todaySchedules = $driver->driverSchedules()
            ->with(['route', 'ejeep'])
            ->where('day_of_week', $currentDay)
            ->where('status', 'active')
            ->orderBy('departure_time')
            ->get();
        
        // Fetch driver's current active trip
        $currentTrip = $driver->driverTrips()
            ->with(['route.stops', 'ejeep', 'passengerLogs.stop'])
            ->where('status', 'in_progress')
            ->first();
        
        // Fetch driver's upcoming trips (scheduled for today)
        $upcomingTrips = $driver->driverTrips()
            ->with(['route', 'ejeep', 'schedule'])
            ->where('status', 'scheduled')
            ->whereDate('scheduled_start_time', now()->toDateString())
            ->orderBy('scheduled_start_time')
            ->get();
        
        // Get unread notifications count
        $unreadNotificationsCount = $driver->notifications()
            ->where('is_read', false)
            ->count();
        
        return view('driver.dashboard', [
            'driver' => $driver,
            'todaySchedules' => $todaySchedules,
            'currentTrip' => $currentTrip,
            'upcomingTrips' => $upcomingTrips,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]);
    }
    
    /**
     * Get assigned trips for the driver (API endpoint).
     */
    public function getAssignedTrips(): JsonResponse
    {
        $driver = Auth::user();
        
        // Get current day of week
        $currentDay = strtolower(now()->format('l'));
        
        // Fetch driver's schedules for current day
        $todaySchedules = $driver->driverSchedules()
            ->with(['route', 'ejeep'])
            ->where('day_of_week', $currentDay)
            ->where('status', 'active')
            ->orderBy('departure_time')
            ->get();
        
        // Fetch driver's current active trip
        $currentTrip = $driver->driverTrips()
            ->with(['route.stops', 'ejeep', 'passengerLogs.stop'])
            ->where('status', 'in_progress')
            ->first();
        
        // Fetch driver's upcoming trips
        $upcomingTrips = $driver->driverTrips()
            ->with(['route', 'ejeep', 'schedule'])
            ->where('status', 'scheduled')
            ->whereDate('scheduled_start_time', now()->toDateString())
            ->orderBy('scheduled_start_time')
            ->get();
        
        // Get unread notifications count
        $unreadNotificationsCount = $driver->notifications()
            ->where('is_read', false)
            ->count();
        
        return response()->json([
            'todaySchedules' => $todaySchedules,
            'currentTrip' => $currentTrip,
            'upcomingTrips' => $upcomingTrips,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'userStatus' => $driver->status,
        ]);
    }
}
