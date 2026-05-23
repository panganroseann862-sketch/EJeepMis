<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\DriverAlert;
use Illuminate\Support\Facades\Notification;

class AlertController extends Controller
{
    public function send(Request $request)
    {
        $driver = auth()->user();
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new DriverAlert($driver->name));
        return back()->with('status', 'Alert sent!');
    }

    public function markAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }
}