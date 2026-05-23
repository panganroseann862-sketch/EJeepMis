<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordPassengerCountRequest;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DriverTripController extends Controller
{
    public function __construct(
        protected TripService $tripService
    ) {}

    /**
     * Display trip details for driver.
     *
     * @param Trip $trip
     * @return View
     */
    public function show(Trip $trip): View
    {
        // Ensure the trip belongs to the authenticated driver
        if ($trip->driver_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this trip.');
        }

        // Load relationships
        $trip->load([
            'route.stops' => function ($query) {
                $query->orderBy('sequence_order');
            },
            'ejeep',
            'passengerLogs.stop'
        ]);

        // Calculate next stop
        $completedStopIds = $trip->passengerLogs->pluck('stop_id')->toArray();
        $nextStop = $trip->route->stops->first(function ($stop) use ($completedStopIds) {
            return !in_array($stop->id, $completedStopIds);
        });

        // Calculate remaining capacity
        $remainingCapacity = $trip->ejeep->passenger_capacity - $trip->current_passenger_count;

        return view('driver.trips.show', compact('trip', 'nextStop', 'remainingCapacity'));
    }

    /**
     * Start a trip (API endpoint).
     *
     * @param Trip $trip
     * @return JsonResponse
     */
    public function start(Trip $trip): JsonResponse
    {
        // Ensure the trip belongs to the authenticated driver
        if ($trip->driver_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to start this trip.'
            ], 403);
        }

        try {
            $updatedTrip = $this->tripService->startTrip($trip);

            return response()->json([
                'success' => true,
                'message' => 'Trip started successfully.',
                'trip' => [
                    'id' => $updatedTrip->id,
                    'status' => $updatedTrip->status,
                    'actual_start_time' => $updatedTrip->actual_start_time?->toIso8601String(),
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to start trip', [
                'trip_id' => $trip->id,
                'driver_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start trip. Please try again.'
            ], 500);
        }
    }

    /**
     * Pause a trip (API endpoint).
     *
     * @param Trip $trip
     * @return JsonResponse
     */
    public function pause(Trip $trip): JsonResponse
    {
        // Ensure the trip belongs to the authenticated driver
        if ($trip->driver_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to pause this trip.'
            ], 403);
        }

        try {
            $updatedTrip = $this->tripService->pauseTrip($trip);

            return response()->json([
                'success' => true,
                'message' => 'Trip paused successfully.',
                'trip' => [
                    'id' => $updatedTrip->id,
                    'status' => $updatedTrip->status,
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to pause trip', [
                'trip_id' => $trip->id,
                'driver_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause trip. Please try again.'
            ], 500);
        }
    }

    /**
     * Complete a trip (API endpoint).
     *
     * @param Trip $trip
     * @return JsonResponse
     */
    public function complete(Trip $trip): JsonResponse
    {
        // Ensure the trip belongs to the authenticated driver
        if ($trip->driver_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to complete this trip.'
            ], 403);
        }

        try {
            $updatedTrip = $this->tripService->completeTrip($trip);

            return response()->json([
                'success' => true,
                'message' => 'Trip completed successfully.',
                'trip' => [
                    'id' => $updatedTrip->id,
                    'status' => $updatedTrip->status,
                    'actual_end_time' => $updatedTrip->actual_end_time?->toIso8601String(),
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to complete trip', [
                'trip_id' => $trip->id,
                'driver_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete trip. Please try again.'
            ], 500);
        }
    }

    /**
     * Record passenger count at a stop (API endpoint).
     *
     * @param RecordPassengerCountRequest $request
     * @param Trip $trip
     * @return JsonResponse
     */
    public function recordPassengerCount(RecordPassengerCountRequest $request, Trip $trip): JsonResponse
    {
        // Ensure the trip belongs to the authenticated driver
        if ($trip->driver_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this trip.'
            ], 403);
        }

        try {
            $validated = $request->validated();

            $stop = \App\Models\Stop::findOrFail($validated['stop_id']);

            $passengerLog = $this->tripService->recordPassengerCount(
                $trip,
                $stop,
                $validated['passenger_count'],
                $validated['boarding_count'],
                $validated['alighting_count']
            );

            // Reload trip to get updated values
            $trip->refresh();

            $remainingCapacity = $trip->ejeep->passenger_capacity - $trip->current_passenger_count;
            $isOverCapacity = $trip->current_passenger_count > $trip->ejeep->passenger_capacity;

            return response()->json([
                'success' => true,
                'message' => 'Passenger count recorded successfully.',
                'data' => [
                    'passenger_log_id' => $passengerLog->id,
                    'current_passenger_count' => $trip->current_passenger_count,
                    'remaining_capacity' => $remainingCapacity,
                    'is_over_capacity' => $isOverCapacity,
                    'warning' => $isOverCapacity ? 'Warning: Passenger count exceeds vehicle capacity!' : null,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to record passenger count', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record passenger count. Please try again.'
            ], 500);
        }
    }

    /**
     * Quick capacity update without stop recording (API endpoint).
     *
     * @param \Illuminate\Http\Request $request
     * @param Trip $trip
     * @return JsonResponse
     */
    public function quickCapacityUpdate(\Illuminate\Http\Request $request, Trip $trip): JsonResponse
    {
        // Ensure the trip belongs to the authenticated driver
        if ($trip->driver_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this trip.'
            ], 403);
        }

        $request->validate([
            'passenger_count' => 'required|integer|min:0',
        ]);

        try {
            $passengerCount = $request->input('passenger_count');
            
            // Update trip passenger count
            $trip->update([
                'current_passenger_count' => $passengerCount,
                'max_passenger_count' => max($trip->max_passenger_count, $passengerCount),
            ]);

            // Check if over capacity and send notification if needed
            if ($passengerCount >= $trip->ejeep->passenger_capacity) {
                $this->tripService->checkCapacity($trip);
            }

            $trip->refresh();
            $trip->load(['ejeep', 'route', 'driver']);

            $remainingCapacity = $trip->ejeep->passenger_capacity - $trip->current_passenger_count;
            $isOverCapacity = $trip->current_passenger_count > $trip->ejeep->passenger_capacity;

            return response()->json([
                'success' => true,
                'message' => 'Capacity updated successfully.',
                'trip' => [
                    'id' => $trip->id,
                    'current_passenger_count' => $trip->current_passenger_count,
                    'max_passenger_count' => $trip->max_passenger_count,
                    'remaining_capacity' => $remainingCapacity,
                    'is_over_capacity' => $isOverCapacity,
                    'ejeep' => [
                        'passenger_capacity' => $trip->ejeep->passenger_capacity,
                    ],
                    'passenger_logs' => $trip->passengerLogs->count(),
                    'route' => [
                        'stops' => $trip->route->stops->count(),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update capacity', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update capacity. Please try again.'
            ], 500);
        }
    }
}

