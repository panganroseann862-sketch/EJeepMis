<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverStatusController extends Controller
{
    /**
     * Show the status change form.
     */
    public function showForm()
    {
        return view('driver.status.change');
    }

    /**
     * Update driver status and notify admin.
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:active,inactive',
            'reason' => 'required|string|min:10|max:500',
        ]);

        $driver = Auth::user();
        $oldStatus = $driver->status;
        
        // Update driver status
        $driver->update([
            'status' => $request->status,
        ]);

        // Generate conversation ID for this status change
        $conversationId = 'status_' . $driver->id . '_' . time();

        // Notify all admins
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            // Create notification with full message content
            Notification::create([
                'user_id' => $admin->id,
                'sender_id' => $driver->id,
                'type' => 'status_change',
                'title' => 'Driver Status Change Request',
                'message' => $request->reason,
                'data' => [
                    'driver_id' => $driver->id,
                    'driver_name' => "{$driver->first_name} {$driver->last_name}",
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'reason' => $request->reason,
                    'conversation_id' => $conversationId,
                ],
                'is_read' => false,
            ]);
            
            // Create the same message in conversation
            Notification::create([
                'user_id' => $admin->id,
                'sender_id' => $driver->id,
                'conversation_id' => $conversationId,
                'type' => 'message',
                'title' => 'Status Change Message',
                'message' => $request->reason,
                'data' => [
                    'sender_name' => $driver->first_name . ' ' . $driver->last_name,
                    'sender_role' => 'driver',
                    'status_change' => true,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                ],
                'is_read' => false,
            ]);
        }

        return redirect()->route('driver.dashboard')
            ->with('success', 'Status updated successfully. Admin has been notified.');
    }
}
