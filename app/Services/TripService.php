<?php

namespace App\Services;

use App\Models\PassengerLog;
use App\Models\Stop;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TripService
{
    /**
     * Start a trip by transitioning from scheduled to in_progress.
     *
     * @param Trip $trip
     * @return Trip
     * @throws \InvalidArgumentException
     */
    public function startTrip(Trip $trip): Trip
    {
        if ($trip->status !== 'scheduled') {
            throw new \InvalidArgumentException("Cannot start trip with status '{$trip->status}'. Trip must be in 'scheduled' status.");
        }

        $trip->update([
            'status' => 'in_progress',
            'actual_start_time' => Carbon::now(),
        ]);

        return $trip->fresh();
    }

    /**
     * Pause a trip by transitioning from in_progress to paused.
     *
     * @param Trip $trip
     * @return Trip
     * @throws \InvalidArgumentException
     */
    public function pauseTrip(Trip $trip): Trip
    {
        if ($trip->status !== 'in_progress') {
            throw new \InvalidArgumentException("Cannot pause trip with status '{$trip->status}'. Trip must be in 'in_progress' status.");
        }

        $trip->update([
            'status' => 'paused',
        ]);

        return $trip->fresh();
    }

    /**
     * Complete a trip by transitioning to completed and recording end time.
     *
     * @param Trip $trip
     * @return Trip
     * @throws \InvalidArgumentException
     */
    public function completeTrip(Trip $trip): Trip
    {
        if ($trip->status !== 'in_progress') {
            throw new \InvalidArgumentException("Cannot complete trip with status '{$trip->status}'. Trip must be in 'in_progress' status.");
        }

        $trip->update([
            'status' => 'completed',
            'actual_end_time' => Carbon::now(),
        ]);

        return $trip->fresh();
    }

    /**
     * Record passenger count at a stop and create a PassengerLog entry.
     *
     * @param Trip $trip
     * @param Stop $stop
     * @param int $passengerCount
     * @param int $boardingCount
     * @param int $alightingCount
     * @return PassengerLog
     */
    public function recordPassengerCount(
        Trip $trip,
        Stop $stop,
        int $passengerCount,
        int $boardingCount,
        int $alightingCount
    ): PassengerLog {
        return DB::transaction(function () use ($trip, $stop, $passengerCount, $boardingCount, $alightingCount) {
            // Check if over capacity
            $isOverCapacity = $passengerCount > $trip->ejeep->passenger_capacity;

            // Create passenger log entry
            $passengerLog = PassengerLog::create([
                'trip_id' => $trip->id,
                'stop_id' => $stop->id,
                'passenger_count' => $passengerCount,
                'boarding_count' => $boardingCount,
                'alighting_count' => $alightingCount,
                'is_over_capacity' => $isOverCapacity,
                'recorded_at' => Carbon::now(),
            ]);

            // Update trip's current passenger count
            $trip->current_passenger_count = $passengerCount;

            // Update max passenger count if current exceeds it
            if ($passengerCount > $trip->max_passenger_count) {
                $trip->max_passenger_count = $passengerCount;
            }

            $trip->save();

            // Send capacity warning notification if at or over capacity
            if ($passengerCount >= $trip->ejeep->passenger_capacity) {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyCapacityWarning($trip->driver, $trip);
            }

            return $passengerLog;
        });
    }


    /**
     * Check if a trip is at or over capacity.
     *
     * @param Trip $trip
     * @return bool
     */
    public function checkCapacity(Trip $trip): bool
    {
        return $trip->current_passenger_count >= $trip->ejeep->passenger_capacity;
    }

    /**
     * Detect and flag route deviation for a trip.
     *
     * @param Trip $trip
     * @return bool
     */
    public function detectRouteDeviation(Trip $trip): bool
    {
        // This is a placeholder implementation
        // In a real system, this would compare GPS coordinates or other tracking data
        // against the expected route to detect deviations
        
        // For now, we just return the current deviation status
        return $trip->has_route_deviation;
    }
}
