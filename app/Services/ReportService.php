<?php

namespace App\Services;

use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Generate daily report for a specific date
     */
    public function generateDailyReport(Carbon $date): array
    {
        $trips = Trip::whereDate('actual_start_time', $date)
            ->with(['ejeep', 'driver', 'route', 'passengerLogs'])
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'total_trips' => $trips->count(),
            'completed_trips' => $trips->where('status', 'completed')->count(),
            'cancelled_trips' => $trips->where('status', 'cancelled')->count(),
            'total_passengers' => $trips->sum('max_passenger_count'),
            'average_passengers' => $trips->avg('max_passenger_count') ?? 0,
            'overcrowding_incidents' => $trips->filter(fn($trip) => $trip->max_passenger_count > $trip->ejeep->passenger_capacity)->count(),
            'route_efficiency' => $this->calculateRouteEfficiencyForTrips($trips),
            'driver_performance' => $this->calculateDriverPerformanceForTrips($trips),
            'schedule_compliance' => $this->calculateScheduleComplianceForTrips($trips),
            'capacity_statistics' => $this->calculateCapacityStatistics($trips),
        ];
    }

    /**
     * Generate weekly report for a date range
     */
    public function generateWeeklyReport(Carbon $startDate, Carbon $endDate): array
    {
        $trips = Trip::whereBetween('actual_start_time', [$startDate, $endDate])
            ->with(['ejeep', 'driver', 'route', 'passengerLogs'])
            ->get();

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_trips' => $trips->count(),
            'completed_trips' => $trips->where('status', 'completed')->count(),
            'cancelled_trips' => $trips->where('status', 'cancelled')->count(),
            'total_passengers' => $trips->sum('max_passenger_count'),
            'average_passengers' => $trips->avg('max_passenger_count') ?? 0,
            'overcrowding_incidents' => $trips->filter(fn($trip) => $trip->max_passenger_count > $trip->ejeep->passenger_capacity)->count(),
            'route_efficiency' => $this->calculateRouteEfficiencyForTrips($trips),
            'driver_performance' => $this->calculateDriverPerformanceForTrips($trips),
            'schedule_compliance' => $this->calculateScheduleComplianceForTrips($trips),
            'capacity_statistics' => $this->calculateCapacityStatistics($trips),
        ];
    }

    /**
     * Calculate route efficiency metrics
     */
    public function calculateRouteEfficiency(Route $route, Carbon $startDate, Carbon $endDate): array
    {
        $trips = Trip::where('route_id', $route->id)
            ->whereBetween('actual_start_time', [$startDate, $endDate])
            ->whereNotNull('actual_end_time')
            ->get();

        $completedTrips = $trips->where('status', 'completed');
        $onTimeTrips = $completedTrips->filter(function ($trip) {
            if (!$trip->scheduled_start_time || !$trip->actual_start_time) {
                return false;
            }
            $diffMinutes = abs($trip->scheduled_start_time->diffInMinutes($trip->actual_start_time));
            return $diffMinutes <= 5;
        });

        $durations = $completedTrips->map(fn($trip) => $trip->getDuration())->filter();

        return [
            'route_id' => $route->id,
            'route_name' => $route->route_name,
            'total_trips' => $trips->count(),
            'completed_trips' => $completedTrips->count(),
            'average_duration_minutes' => $durations->avg() ?? 0,
            'on_time_percentage' => $completedTrips->count() > 0 
                ? round(($onTimeTrips->count() / $completedTrips->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Calculate driver performance metrics
     */
    public function calculateDriverPerformance(User $driver, Carbon $startDate, Carbon $endDate): array
    {
        $trips = Trip::where('driver_id', $driver->id)
            ->whereBetween('actual_start_time', [$startDate, $endDate])
            ->get();

        $completedTrips = $trips->where('status', 'completed');
        $onTimeTrips = $completedTrips->filter(function ($trip) {
            if (!$trip->scheduled_start_time || !$trip->actual_start_time) {
                return false;
            }
            $diffMinutes = abs($trip->scheduled_start_time->diffInMinutes($trip->actual_start_time));
            return $diffMinutes <= 5;
        });

        $passengerLoads = $completedTrips->map(fn($trip) => $trip->max_passenger_count)->filter();

        return [
            'driver_id' => $driver->id,
            'driver_name' => $driver->first_name . ' ' . $driver->last_name,
            'total_trips' => $trips->count(),
            'completed_trips' => $completedTrips->count(),
            'cancelled_trips' => $trips->where('status', 'cancelled')->count(),
            'schedule_adherence_percentage' => $completedTrips->count() > 0
                ? round(($onTimeTrips->count() / $completedTrips->count()) * 100, 2)
                : 0,
            'average_passenger_load' => $passengerLoads->avg() ?? 0,
        ];
    }

    /**
     * Calculate schedule compliance rate
     */
    public function calculateScheduleCompliance(Carbon $startDate, Carbon $endDate): float
    {
        $trips = Trip::whereBetween('actual_start_time', [$startDate, $endDate])
            ->whereNotNull('scheduled_start_time')
            ->whereNotNull('actual_start_time')
            ->get();

        if ($trips->isEmpty()) {
            return 0.0;
        }

        $onTimeTrips = $trips->filter(function ($trip) {
            $diffMinutes = abs($trip->scheduled_start_time->diffInMinutes($trip->actual_start_time));
            return $diffMinutes <= 5;
        });

        return round(($onTimeTrips->count() / $trips->count()) * 100, 2);
    }

    /**
     * Calculate route efficiency for a collection of trips
     */
    private function calculateRouteEfficiencyForTrips(Collection $trips): array
    {
        $routeData = [];

        foreach ($trips->groupBy('route_id') as $routeId => $routeTrips) {
            $route = $routeTrips->first()->route;
            $completedTrips = $routeTrips->where('status', 'completed');
            
            $onTimeTrips = $completedTrips->filter(function ($trip) {
                if (!$trip->scheduled_start_time || !$trip->actual_start_time) {
                    return false;
                }
                $diffMinutes = abs($trip->scheduled_start_time->diffInMinutes($trip->actual_start_time));
                return $diffMinutes <= 5;
            });

            $durations = $completedTrips->map(fn($trip) => $trip->getDuration())->filter();

            $routeData[] = [
                'route_name' => $route->route_name,
                'total_trips' => $routeTrips->count(),
                'completed_trips' => $completedTrips->count(),
                'average_duration_minutes' => round($durations->avg() ?? 0, 2),
                'on_time_percentage' => $completedTrips->count() > 0
                    ? round(($onTimeTrips->count() / $completedTrips->count()) * 100, 2)
                    : 0,
            ];
        }

        return $routeData;
    }

    /**
     * Calculate driver performance for a collection of trips
     */
    private function calculateDriverPerformanceForTrips(Collection $trips): array
    {
        $driverData = [];

        foreach ($trips->groupBy('driver_id') as $driverId => $driverTrips) {
            $driver = $driverTrips->first()->driver;
            $completedTrips = $driverTrips->where('status', 'completed');
            
            $onTimeTrips = $completedTrips->filter(function ($trip) {
                if (!$trip->scheduled_start_time || !$trip->actual_start_time) {
                    return false;
                }
                $diffMinutes = abs($trip->scheduled_start_time->diffInMinutes($trip->actual_start_time));
                return $diffMinutes <= 5;
            });

            $passengerLoads = $completedTrips->map(fn($trip) => $trip->max_passenger_count)->filter();

            $driverData[] = [
                'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                'total_trips' => $driverTrips->count(),
                'completed_trips' => $completedTrips->count(),
                'schedule_adherence_percentage' => $completedTrips->count() > 0
                    ? round(($onTimeTrips->count() / $completedTrips->count()) * 100, 2)
                    : 0,
                'average_passenger_load' => round($passengerLoads->avg() ?? 0, 2),
            ];
        }

        return $driverData;
    }

    /**
     * Calculate schedule compliance for a collection of trips
     */
    private function calculateScheduleComplianceForTrips(Collection $trips): float
    {
        $tripsWithSchedule = $trips->filter(function ($trip) {
            return $trip->scheduled_start_time && $trip->actual_start_time;
        });

        if ($tripsWithSchedule->isEmpty()) {
            return 0.0;
        }

        $onTimeTrips = $tripsWithSchedule->filter(function ($trip) {
            $diffMinutes = abs($trip->scheduled_start_time->diffInMinutes($trip->actual_start_time));
            return $diffMinutes <= 5;
        });

        return round(($onTimeTrips->count() / $tripsWithSchedule->count()) * 100, 2);
    }

    /**
     * Calculate capacity statistics for a collection of trips
     */
    private function calculateCapacityStatistics(Collection $trips): array
    {
        $completedTrips = $trips->where('status', 'completed');
        
        if ($completedTrips->isEmpty()) {
            return [
                'average_load' => 0,
                'max_load' => 0,
                'overcrowding_incidents' => 0,
                'average_capacity_utilization_percentage' => 0,
            ];
        }

        $loads = $completedTrips->map(fn($trip) => $trip->max_passenger_count);
        $overcrowdingIncidents = $completedTrips->filter(function ($trip) {
            return $trip->max_passenger_count > $trip->ejeep->passenger_capacity;
        });

        $utilizationPercentages = $completedTrips->map(function ($trip) {
            if ($trip->ejeep->passenger_capacity == 0) {
                return 0;
            }
            return ($trip->max_passenger_count / $trip->ejeep->passenger_capacity) * 100;
        });

        return [
            'average_load' => round($loads->avg() ?? 0, 2),
            'max_load' => $loads->max() ?? 0,
            'overcrowding_incidents' => $overcrowdingIncidents->count(),
            'average_capacity_utilization_percentage' => round($utilizationPercentages->avg() ?? 0, 2),
        ];
    }
}
