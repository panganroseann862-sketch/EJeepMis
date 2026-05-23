<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    /**
     * Display messages for admin.
     */
    public function adminIndex(Request $request)
    {
        $user = Auth::user();
        
        // Get all conversations (grouped by conversation_id)
        $conversations = Notification::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('sender_id', $user->id);
        })
        ->whereNotNull('conversation_id')
        ->with(['user', 'sender'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('conversation_id');
        
        // Get list of drivers for starting new conversations
        $drivers = User::where('role', 'driver')->orderBy('first_name')->get();
        
        // Check if there's a conversation to open from query parameter
        $openConversation = $request->query('conversation');
        
        return view('admin.messages.index', compact('conversations', 'drivers', 'openConversation'));
    }
    
    /**
     * Display messages for driver.
     */
    public function driverIndex()
    {
        $user = Auth::user();
        
        // Get all conversations
        $conversations = Notification::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('sender_id', $user->id);
        })
        ->whereNotNull('conversation_id')
        ->with(['user', 'sender'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('conversation_id');
        
        // Get list of admins
        $admins = User::where('role', 'admin')->orderBy('first_name')->get();
        
        return view('driver.messages.index', compact('conversations', 'admins'));
    }
    
    /**
     * Send a message from admin to driver.
     */
    public function adminSend(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
            'message' => 'required|string|min:1|max:1000',
            'conversation_id' => 'nullable|string',
        ]);
        
        $admin = Auth::user();
        $driver = User::findOrFail($request->driver_id);
        
        // Generate conversation ID if not provided
        $conversationId = $request->conversation_id ?? 'conv_' . Str::uuid();
        
        // Create message notification for driver
        Notification::create([
            'user_id' => $driver->id,
            'sender_id' => $admin->id,
            'conversation_id' => $conversationId,
            'type' => 'message',
            'title' => 'Message from Admin',
            'message' => $request->message,
            'data' => [
                'sender_name' => $admin->first_name . ' ' . $admin->last_name,
                'sender_role' => 'admin',
            ],
            'is_read' => false,
        ]);
        
        return back()->with('success', 'Message sent successfully.');
    }
    
    /**
     * Send a message from driver to admin.
     */
    public function driverSend(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id',
            'message' => 'required|string|min:1|max:1000',
            'conversation_id' => 'nullable|string',
        ]);
        
        $driver = Auth::user();
        $admin = User::findOrFail($request->admin_id);
        
        // Generate conversation ID if not provided
        $conversationId = $request->conversation_id ?? 'conv_' . Str::uuid();
        
        // Create message notification for admin
        Notification::create([
            'user_id' => $admin->id,
            'sender_id' => $driver->id,
            'conversation_id' => $conversationId,
            'type' => 'message',
            'title' => 'Message from Driver',
            'message' => $request->message,
            'data' => [
                'sender_name' => $driver->first_name . ' ' . $driver->last_name,
                'sender_role' => 'driver',
            ],
            'is_read' => false,
        ]);
        
        return back()->with('success', 'Message sent successfully.');
    }
    
    /**
     * Get conversation messages (AJAX).
     */
    public function getConversation(Request $request, $conversationId)
    {
        $user = Auth::user();
        
        $messages = Notification::where('conversation_id', $conversationId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('sender_id', $user->id);
            })
            ->with(['user', 'sender'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Mark messages as read
        Notification::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        
        return response()->json(['messages' => $messages]);
    }
}