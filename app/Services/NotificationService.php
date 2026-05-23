<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;

class NotificationService
{
    /**
     * Notify driver about route update
     */
    public function notifyRouteUpdate(User $driver, Route $route): Notification
    {
        return $this->sendToDriver(
            $driver,
            'route_update',
            'Route Updated',
            "Route {$route->route_name} ({$route->route_code}) has been updated. Please review the changes.",
            [
                'route_id' => $route->id,
                'route_name' => $route->route_name,
                'route_code' => $route->route_code,
            ]
        );
    }

    /**
     * Notify driver about schedule change
     */
    public function notifyScheduleChange(User $driver, Schedule $schedule): Notification
    {
        $route = $schedule->route;
        
        return $this->sendToDriver(
            $driver,
            'schedule_change',
            'Schedule Changed',
            "Your schedule for route {$route->route_name} on {$schedule->day_of_week} at {$schedule->departure_time->format('H:i')} has been modified.",
            [
                'schedule_id' => $schedule->id,
                'route_id' => $schedule->route_id,
                'route_name' => $route->route_name,
                'day_of_week' => $schedule->day_of_week,
                'departure_time' => $schedule->departure_time->format('H:i'),
            ]
        );
    }

    /**
     * Notify driver about capacity warning
     */
    public function notifyCapacityWarning(User $driver, Trip $trip): Notification
    {
        $ejeep = $trip->ejeep;
        $route = $trip->route;
        
        return $this->sendToDriver(
            $driver,
            'capacity_warning',
            'Capacity Warning',
            "Your trip on route {$route->route_name} has reached or exceeded capacity. Current: {$trip->current_passenger_count}, Capacity: {$ejeep->passenger_capacity}",
            [
                'trip_id' => $trip->id,
                'route_id' => $trip->route_id,
                'route_name' => $route->route_name,
                'current_passenger_count' => $trip->current_passenger_count,
                'passenger_capacity' => $ejeep->passenger_capacity,
            ]
        );
    }

    /**
     * Generic method to send notification to driver
     */
    public function sendToDriver(User $driver, string $type, string $title, string $message, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $driver->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }
}
