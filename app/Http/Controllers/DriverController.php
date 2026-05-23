<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers.
     */
    public function index(): View
    {
        $drivers = User::drivers()
            ->withCount(['driverTrips as completed_trips_count' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.drivers.index', compact('drivers'));
    }

    /**
     * Show the form for creating a new driver.
     */
    public function create(): View
    {
        return view('admin.drivers.create');
    }

    /**
     * Store a newly created driver in storage.
     */
    public function store(StoreDriverRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $validated['role'] = 'driver';
            $validated['status'] = 'active';

            $driver = User::create($validated);

            return redirect()
                ->route('admin.drivers.show', $driver)
                ->with('success', 'Driver created successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to create driver', [
                'error' => $e->getMessage(),
                'data' => $request->except('password'),
            ]);
            
            return redirect()->back()
                ->withInput($request->except('password'))
                ->with('error', 'Failed to create driver. Please try again.');
        }
    }

    /**
     * Display the specified driver with performance metrics.
     */
    public function show(User $driver): View
    {
        $driver->load(['driverSchedules.route', 'driverSchedules.ejeep']);

        // Calculate performance metrics
        $completedTrips = $driver->driverTrips()->where('status', 'completed')->get();
        $totalTrips = $completedTrips->count();

        // Calculate on-time trips (started within 5 minutes of scheduled time)
        $onTimeTrips = $completedTrips->filter(function ($trip) {
            if (! $trip->actual_start_time || ! $trip->scheduled_start_time) {
                return false;
            }
            $diffInMinutes = abs($trip->actual_start_time->diffInMinutes($trip->scheduled_start_time));

            return $diffInMinutes <= 5;
        })->count();

        $scheduleAdherence = $totalTrips > 0 ? round(($onTimeTrips / $totalTrips) * 100, 2) : 0;

        // Calculate average passenger load
        $avgPassengerLoad = $completedTrips->avg('max_passenger_count') ?? 0;

        return view('admin.drivers.show', compact(
            'driver',
            'totalTrips',
            'scheduleAdherence',
            'avgPassengerLoad'
        ));
    }

    /**
     * Show the form for editing the specified driver.
     */
    public function edit(User $driver): View
    {
        return view('admin.drivers.edit', compact('driver'));
    }

    /**
     * Update the specified driver in storage.
     */
    public function update(UpdateDriverRequest $request, User $driver): RedirectResponse
    {
        try {
            $driver->update($request->validated());

            return redirect()
                ->route('admin.drivers.show', $driver)
                ->with('success', 'Driver updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to update driver', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
                'data' => $request->except('password'),
            ]);
            
            return redirect()->back()
                ->withInput($request->except('password'))
                ->with('error', 'Failed to update driver. Please try again.');
        }
    }

    /**
     * Remove the specified driver from storage.
     */
    public function destroy(User $driver): RedirectResponse
    {
        try {
            // Check if driver has active trips
            $activeTrips = $driver->driverTrips()->whereIn('status', ['scheduled', 'in_progress', 'paused'])->count();
            
            if ($activeTrips > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete driver with active or scheduled trips.');
            }
            
            $driver->delete();

            return redirect()
                ->route('admin.drivers.index')
                ->with('success', 'Driver deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to delete driver', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to delete driver. Please try again.');
        }
    }
}
