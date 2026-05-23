<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\StudentAuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverDashboardController;
use App\Http\Controllers\DriverNotificationController;
use App\Http\Controllers\DriverStatusController;
use App\Http\Controllers\DriverTripController;
use App\Http\Controllers\EjeepController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TripMonitoringController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\EmergencyController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/student/login', [StudentAuthController::class, 'showLoginForm'])->name('student.login');
    Route::post('/student/login', [StudentAuthController::class, 'login'])->name('student.login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Student Routes
    Route::post('/student/logout', [StudentAuthController::class, 'logout'])->name('student.logout');
    Route::prefix('student')->name('student.')->middleware('student')->group(function () {
        Route::get('/dashboard', function () {
            return view('student.dashboard');
        })->name('dashboard');

        // Driver Logs API — returns real-time trip data for the student dashboard
        Route::get('/driver-logs', function () {
            $logs = \App\Models\Trip::with(['driver', 'ejeep', 'route'])
                ->whereIn('status', ['in_progress', 'paused', 'scheduled'])
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($trip) {
                    // Use first_name + last_name since User model has no single 'name' field
                    $driverName = $trip->driver
                        ? trim($trip->driver->first_name . ' ' . $trip->driver->last_name)
                        : 'Unknown Driver';

                    // Use route_name since Route model uses 'route_name' not 'name'
                    $routeName = $trip->route->route_name ?? 'Unknown Route';

                    // Use vehicle_number for jeep label
                    $jeepLabel = $trip->ejeep
                        ? 'E-Jeep ' . $trip->ejeep->vehicle_number
                        : 'E-Jeep #' . $trip->ejeep_id;

                    return [
                        'driver'   => $driverName,
                        'jeep'     => $jeepLabel,
                        'action'   => $trip->status === 'in_progress' ? 'Departed' : 'Standby',
                        'location' => $routeName,
                        'status'   => match($trip->status) {
                            'in_progress' => 'En Route',
                            'paused'      => 'Loading',
                            default       => 'Standby',
                        },
                        'time' => $trip->updated_at->format('h:i A'),
                    ];
                });

            return response()->json($logs);
        })->name('driver-logs');
    });

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/realtime-data', [AdminDashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');
        Route::resource('ejeeps', EjeepController::class);
        Route::resource('drivers', DriverController::class);
        Route::resource('routes', RouteController::class);
        Route::resource('schedules', ScheduleController::class);

        Route::get('/trips', [TripMonitoringController::class, 'index'])->name('trips.index');
        Route::get('/trips/{trip}', [TripMonitoringController::class, 'show'])->name('trips.show');
        Route::get('/trips-api/active', [TripMonitoringController::class, 'getActiveTrips'])->name('trips.active');
        Route::get('/trips-api/capacity-alerts', [TripMonitoringController::class, 'getCapacityAlerts'])->name('trips.capacity-alerts');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/daily', [ReportController::class, 'generateDaily'])->name('reports.daily');
        Route::post('/reports/weekly', [ReportController::class, 'generateWeekly'])->name('reports.weekly');

        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [AdminNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('/notifications/{notification}/mark-as-read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::post('/notifications/{notification}/reply', [AdminNotificationController::class, 'reply'])->name('notifications.reply');

        Route::get('/messages', [MessageController::class, 'adminIndex'])->name('messages.index');
        Route::post('/messages/send', [MessageController::class, 'adminSend'])->name('messages.send');
        Route::get('/messages/conversation/{conversationId}', [MessageController::class, 'getConversation'])->name('messages.conversation');

        Route::post('/emergency/send', [EmergencyController::class, 'sendAlert'])->name('emergency.send');
        Route::post('/emergency/{id}/resolve', [EmergencyController::class, 'resolveAlert'])->name('emergency.resolve');
    });

    // Driver Routes
    Route::middleware('driver')->prefix('driver')->name('driver.')->group(function () {
        Route::get('/dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/assigned-trips', [DriverDashboardController::class, 'getAssignedTrips'])->name('dashboard.assigned-trips');

        Route::get('/trips/{trip}', [DriverTripController::class, 'show'])->name('trips.show');
        Route::post('/trips/{trip}/start', [DriverTripController::class, 'start'])->name('trips.start');
        Route::post('/trips/{trip}/pause', [DriverTripController::class, 'pause'])->name('trips.pause');
        Route::post('/trips/{trip}/complete', [DriverTripController::class, 'complete'])->name('trips.complete');
        Route::post('/trips/{trip}/passenger-count', [DriverTripController::class, 'recordPassengerCount'])->name('trips.passenger-count');
        Route::post('/trips/{trip}/quick-capacity-update', [DriverTripController::class, 'quickCapacityUpdate'])->name('trips.quick-capacity-update');

        Route::get('/notifications', [DriverNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [DriverNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('/notifications/{notification}/mark-as-read', [DriverNotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');

        Route::get('/messages', [MessageController::class, 'driverIndex'])->name('messages.index');
        Route::post('/messages/send', [MessageController::class, 'driverSend'])->name('messages.send');
        Route::get('/messages/conversation/{conversationId}', [MessageController::class, 'getConversation'])->name('messages.conversation');

        Route::get('/status/change', [DriverStatusController::class, 'showForm'])->name('status.change');
        Route::post('/status/update', [DriverStatusController::class, 'updateStatus'])->name('status.update');

        Route::post('/emergency/send', [EmergencyController::class, 'sendAlert'])->name('emergency.send');
    });
});