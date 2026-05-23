@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Page Header --}}
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Driver Dashboard</h2>
                <p class="text-sm text-gray-500 mt-1">Welcome back, {{ Auth::user()->first_name }}! Here's your schedule and trip status.</p>
            </div>
            <a href="{{ route('driver.status.change') }}"
               class="inline-flex items-center gap-2 bg-blue-800 hover:bg-blue-900 text-white font-medium py-2 px-4 rounded-lg text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Change Status
            </a>
        </div>

        {{-- Emergency Banner --}}
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-red-700">Emergency SOS</p>
                        <p class="text-xs text-red-500 mt-0.5">Use this only if you need immediate help from the admin.</p>
                    </div>
                </div>
                <form action="{{ route('driver.emergency.send') }}" method="POST"
                      onsubmit="return confirm('Send emergency alert to admin?')">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm py-2 px-4 rounded-lg transition flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Send Emergency
                    </button>
                </form>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">

            {{-- Current Status --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</p>
                        <p id="current-status-display" class="text-2xl font-semibold mt-2">
                            @if($currentTrip)
                                <span class="text-green-600">On Trip</span>
                            @elseif(Auth::user()->status === 'active')
                                <span class="text-green-600">Active</span>
                            @else
                                <span class="text-gray-500">Inactive</span>
                            @endif
                        </p>
                    </div>
                    <div id="current-status-icon"
                         class="w-12 h-12 rounded-xl flex items-center justify-center
                                {{ $currentTrip || Auth::user()->status === 'active' ? 'bg-green-50' : 'bg-gray-100' }}">
                        <svg class="w-6 h-6 {{ $currentTrip || Auth::user()->status === 'active' ? 'text-green-600' : 'text-gray-400' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Today's Schedule --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Today's Schedule</p>
                        <p id="schedule-count" class="text-2xl font-semibold text-gray-800 mt-2">{{ $todaySchedules->count() }}</p>
                        <p class="text-xs text-gray-400 mt-1">scheduled trips</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Notifications --}}
            <a href="{{ route('driver.notifications.index') }}"
               class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition block">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Notifications</p>
                        <p id="notification-count" class="text-2xl font-semibold text-gray-800 mt-2">{{ $unreadNotificationsCount }}</p>
                        <p class="text-xs text-gray-400 mt-1">unread messages</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                </div>
            </a>

        </div>

        {{-- Current Trip Section --}}
        @if($currentTrip)
        <div class="bg-white border border-green-200 rounded-xl shadow-sm mb-6 overflow-hidden">
            <div class="bg-green-600 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-white font-semibold text-lg">Current Trip in Progress</h3>
                    <p class="text-green-100 text-sm mt-0.5">{{ $currentTrip->route->route_name }}</p>
                </div>
                <span class="bg-white bg-opacity-20 text-white text-sm font-medium px-3 py-1 rounded-lg">
                    {{ $currentTrip->ejeep->vehicle_number }}
                </span>
            </div>

            <div class="p-6">
                {{-- Quick Capacity Update --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-5">
                    <p class="text-sm font-medium text-gray-700 mb-3">Quick Capacity Update</p>
                    <form id="quick-capacity-form" class="flex gap-3 items-end">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1">Current Passenger Count</label>
                            <input type="number"
                                   id="quick-passenger-count"
                                   name="passenger_count"
                                   min="0"
                                   max="{{ $currentTrip->ejeep->passenger_capacity }}"
                                   value="{{ $currentTrip->current_passenger_count }}"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-200 text-gray-900 font-semibold text-base focus:border-green-500 focus:ring-green-500"
                                   required>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-medium text-sm py-2 px-4 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update
                        </button>
                    </form>
                    <p class="text-xs text-gray-400 mt-2">Quickly update passenger count without recording at a specific stop.</p>
                </div>

                {{-- Trip Stats --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Started</p>
                        <p class="text-xl font-semibold text-gray-800 mt-1">
                            {{ $currentTrip->actual_start_time ? $currentTrip->actual_start_time->format('g:i A') : '—' }}
                        </p>
                        @if($currentTrip->actual_start_time)
                            <p class="text-xs text-gray-400 mt-1">{{ $currentTrip->actual_start_time->diffForHumans() }}</p>
                        @endif
                    </div>
                    <div id="current-trip-passengers" class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Passengers</p>
                        <p class="text-xl font-semibold text-gray-800 mt-1">
                            {{ $currentTrip->current_passenger_count }}/{{ $currentTrip->ejeep->passenger_capacity }}
                        </p>
                        @if($currentTrip->current_passenger_count >= $currentTrip->ejeep->passenger_capacity)
                            <p class="text-xs text-red-500 font-medium mt-1">⚠ At/Over Capacity</p>
                        @else
                            <p class="text-xs text-gray-400 mt-1">{{ $currentTrip->getRemainingCapacity() }} seats available</p>
                        @endif
                    </div>
                    <div id="current-trip-progress" class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Route Progress</p>
                        <p class="text-xl font-semibold text-gray-800 mt-1">
                            {{ $currentTrip->passengerLogs->count() }}/{{ $currentTrip->route->stops->count() }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">stops completed</p>
                    </div>
                </div>

                <a href="{{ route('driver.trips.show', $currentTrip) }}"
                   class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-medium text-sm py-2 px-4 rounded-lg transition">
                    View Trip Details
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        @endif

        {{-- Today's Schedule & Upcoming Trips --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Today's Schedule --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Today's Schedule</h3>
                </div>
                <div class="p-5">
                    @if($todaySchedules->count() > 0)
                        <div class="space-y-3">
                            @foreach($todaySchedules as $schedule)
                                @php
                                    $scheduleTime = \Carbon\Carbon::parse($schedule->departure_time);
                                    $isPast = $scheduleTime->lt(\Carbon\Carbon::now());
                                @endphp
                                <div class="border border-gray-100 rounded-xl p-4 hover:bg-gray-50 transition">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xl font-semibold text-gray-800">{{ $scheduleTime->format('g:i A') }}</span>
                                                @if($isPast)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                                        Completed
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block"></span>
                                                        Upcoming
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-sm font-medium text-gray-700 mt-1">{{ $schedule->route->route_name }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $schedule->ejeep->vehicle_number }} &middot; {{ $schedule->ejeep->passenger_capacity }} seats</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400">No schedules for today</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Upcoming Trips --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Upcoming Trips</h3>
                </div>
                <div class="p-5">
                    @if($upcomingTrips->count() > 0)
                        <div class="space-y-3">
                            @foreach($upcomingTrips as $trip)
                                <div class="border border-gray-100 rounded-xl p-4 hover:bg-gray-50 transition">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 inline-block"></span>
                                                    Scheduled
                                                </span>
                                                <span class="text-sm font-medium text-gray-700">{{ $trip->ejeep->vehicle_number }}</span>
                                            </div>
                                            <p class="text-sm font-medium text-gray-700 mt-1">{{ $trip->route->route_name }}</p>
                                            <div class="flex items-center gap-3 mt-1">
                                                <span class="flex items-center gap-1 text-xs text-gray-400">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    {{ $trip->scheduled_start_time->format('g:i A') }}
                                                </span>
                                                <span class="text-xs text-gray-400">{{ $trip->scheduled_start_time->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400">No upcoming trips</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('quick-capacity-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const passengerCount = document.getElementById('quick-passenger-count').value;
        const tripId = Number("{{ $currentTrip->id ?? '' }}") || null;
        if (!tripId) return;

        fetch(`/driver/trips/${tripId}/quick-capacity-update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ passenger_count: parseInt(passengerCount) })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateCurrentTripInfo(data.trip);
                showNotification('Capacity updated successfully!', 'success');
            } else {
                showNotification(data.message || 'Failed to update capacity', 'error');
            }
        })
        .catch(() => showNotification('Failed to update capacity', 'error'));
    });

    function showNotification(message, type) {
        const el = document.createElement('div');
        el.className = `fixed top-4 right-4 px-5 py-3 rounded-lg shadow-lg text-white text-sm z-50 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    }

    let pollingInterval;

    function updateDriverDashboard() {
        fetch('{!! route("driver.dashboard.assigned-trips") !!}', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            const notifEl = document.getElementById('notification-count');
            if (notifEl) notifEl.textContent = data.unreadNotificationsCount;

            const statusDisplay = document.getElementById('current-status-display');
            const statusIcon = document.getElementById('current-status-icon');
            if (statusDisplay && data.userStatus) {
                let text = 'Inactive', color = 'gray';
                if (data.currentTrip) { text = 'On Trip'; color = 'green'; }
                else if (data.userStatus === 'active') { text = 'Active'; color = 'green'; }
                statusDisplay.innerHTML = `<span class="text-${color}-600">${text}</span>`;
                if (statusIcon) {
                    statusIcon.className = `w-12 h-12 rounded-xl flex items-center justify-center bg-${color}-50`;
                    statusIcon.querySelector('svg').className = `w-6 h-6 text-${color}-${color === 'gray' ? '400' : '600'}`;
                }
            }

            if (data.currentTrip) updateCurrentTripInfo(data.currentTrip);

            const schedEl = document.getElementById('schedule-count');
            if (schedEl) schedEl.textContent = data.todaySchedules.length;
        })
        .catch(err => console.error('Dashboard poll error:', err));
    }

    function updateCurrentTripInfo(trip) {
        const passengerEl = document.getElementById('current-trip-passengers');
        if (passengerEl) {
            const over = trip.current_passenger_count >= trip.ejeep.passenger_capacity;
            const remaining = trip.ejeep.passenger_capacity - trip.current_passenger_count;
            passengerEl.innerHTML = `
                <p class="text-xs text-gray-500 uppercase tracking-wider">Passengers</p>
                <p class="text-xl font-semibold text-gray-800 mt-1">${trip.current_passenger_count}/${trip.ejeep.passenger_capacity}</p>
                ${over
                    ? '<p class="text-xs text-red-500 font-medium mt-1">⚠ At/Over Capacity</p>'
                    : `<p class="text-xs text-gray-400 mt-1">${remaining} seats available</p>`}
            `;
        }
        const progressEl = document.getElementById('current-trip-progress');
        if (progressEl) {
            const stops = trip.passenger_logs ? trip.passenger_logs.length : 0;
            const total = trip.route?.stops?.length ?? 0;
            progressEl.innerHTML = `
                <p class="text-xs text-gray-500 uppercase tracking-wider">Route Progress</p>
                <p class="text-xl font-semibold text-gray-800 mt-1">${stops}/${total}</p>
                <p class="text-xs text-gray-400 mt-1">stops completed</p>
            `;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateDriverDashboard();
        pollingInterval = setInterval(updateDriverDashboard, 10000);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { clearInterval(pollingInterval); }
        else { updateDriverDashboard(); pollingInterval = setInterval(updateDriverDashboard, 10000); }
    });

    window.addEventListener('beforeunload', function () { clearInterval(pollingInterval); });
</script>
@endpush
@endsection