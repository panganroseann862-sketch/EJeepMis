<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DriverAlert extends Notification
{
    use Queueable;

    public $driverName;

    public function __construct($driverName)
    {
        $this->driverName = $driverName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Emergency alert mula kay: " . $this->driverName,
            'driver_name' => $this->driverName,
        ];
    }
}