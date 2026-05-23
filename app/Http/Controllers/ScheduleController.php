<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Ejeep;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules.
     */
    public function index(): View
    {
        $schedules = Schedule::with(['route', 'ejeep', 'driver'])
            ->orderBy('day_of_week')
            ->orderBy('departure_time')
            ->paginate(15);

        return view('admin.schedules.index', compact('schedules'));
    }

    /**
     * Show the form for creating a new schedule.
     */
    public function create(): View
    {
        $routes = Route::active()->orderBy('route_code')->get();
        $ejeeps = Ejeep::where('operational_status', '!=', 'maintenance')
            ->orderBy('vehicle_number')
            ->get();
        $drivers = User::drivers()->active()->orderBy('first_name')->get();

        return view('admin.schedules.create', compact('routes', 'ejeeps', 'drivers'));
    }

    /**
     * Store a newly created schedule in storage.
     */
    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        try {
            $schedule = Schedule::create($request->validated());

            return redirect()
                ->route('admin.schedules.show', $schedule)
                ->with('success', 'Schedule created successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to create schedule', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create schedule. Please try again.');
        }
    }

    /**
     * Display the specified schedule.
     */
    public function show(Schedule $schedule): View
    {
        $schedule->load(['route', 'ejeep', 'driver']);

        return view('admin.schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified schedule.
     */
    public function edit(Schedule $schedule): View
    {
        $routes = Route::active()->orderBy('route_code')->get();
        $ejeeps = Ejeep::where('operational_status', '!=', 'maintenance')
            ->orderBy('vehicle_number')
            ->get();
        $drivers = User::drivers()->active()->orderBy('first_name')->get();

        return view('admin.schedules.edit', compact('schedule', 'routes', 'ejeeps', 'drivers'));
    }

    /**
     * Update the specified schedule in storage.
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        try {
            // Get the driver before update to notify them
            $driver = $schedule->driver;

            $schedule->update($request->validated());

            // Reload the schedule with relationships
            $schedule->load('route');

            // Notify the driver of the schedule change
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->notifyScheduleChange($driver, $schedule);

            return redirect()
                ->route('admin.schedules.show', $schedule)
                ->with('success', 'Schedule updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to update schedule', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update schedule. Please try again.');
        }
    }


    /**
     * Remove the specified schedule from storage.
     */
    public function destroy(Schedule $schedule): RedirectResponse
    {
        try {
            // Check if schedule has active trips
            $activeTrips = $schedule->trips()->whereIn('status', ['scheduled', 'in_progress', 'paused'])->count();
            
            if ($activeTrips > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete schedule with active or scheduled trips.');
            }
            
            $schedule->delete();

            return redirect()
                ->route('admin.schedules.index')
                ->with('success', 'Schedule deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to delete schedule', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to delete schedule. Please try again.');
        }
    }
}
