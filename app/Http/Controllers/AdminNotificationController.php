<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    /**
     * Display admin notifications.
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::user()->id)
            ->whereNull('parent_id') // Only show parent notifications, not replies
            ->whereNull('conversation_id') // Exclude messages (they appear in Messages tab)
            ->with(['replies.user', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id != Auth::user()->id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Reply to a notification.
     */
    public function reply(Request $request, Notification $notification)
    {
        // Ensure the notification belongs to the authenticated admin
        if ($notification->user_id != Auth::user()->id) {
            abort(403, 'Unauthorized to reply to this notification');
        }

        $request->validate([
            'message' => 'required|string|min:5|max:500',
        ]);

        // Get the driver who sent the original notification
        $driverId = $notification->data['driver_id'] ?? null;
        
        if (!$driverId) {
            return back()->with('error', 'Cannot reply to this notification.');
        }

        // Create reply notification for the driver
        Notification::create([
            'user_id' => $driverId,
            'sender_id' => Auth::user()->id,
            'parent_id' => $notification->id,
            'type' => 'admin_reply',
            'title' => 'Admin Reply to Your Status Change',
            'message' => $request->message,
            'data' => [
                'admin_id' => Auth::user()->id,
                'admin_name' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
                'original_notification_id' => $notification->id,
            ],
            'is_read' => false,
        ]);

        // Mark the original notification as read
        $notification->markAsRead();

        return back()->with('success', 'Reply sent successfully.');
    }

    /**
     * Get unread notification count for polling
     */
    public function unreadCount()
    {
        $count = Notification::where('user_id', Auth::user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}

