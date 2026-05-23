<?php

namespace App\Http\Controllers;

use App\Models\EmergencyAlert;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmergencyController extends Controller
{
    public function sendAlert(Request $request)
    {
        $driver = Auth::user();

        EmergencyAlert::create([
            'driver_id' => $driver->id,
            'location' => 'E-Jeep Unit ' . $driver->id,
            'status' => 'pending',
        ]);

        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'sender_id' => $driver->id,
                'type' => 'emergency',
                'title' => '🚨 EMERGENCY ALERT!',
                'message' => 'Driver ' . $driver->first_name . ' ' . $driver->last_name . ' needs assistance!',
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Emergency alert sent to all admins!');
    }

    public function resolveAlert($id)
    {
        $alert = EmergencyAlert::findOrFail($id);
        
        $alert->update([
            'status' => 'resolved'
        ]);

        return back()->with('success', 'Emergency alert has been resolved.');
    }
}