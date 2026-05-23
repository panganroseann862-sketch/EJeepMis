@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold" style="color:#12196B;">Admin Dashboard</h1>
                <p class="text-gray-500 mt-1">Welcome back, {{ Auth::user()->first_name }}! Here's your fleet overview.</p>
            </div>
            <a href="{{ route('admin.notifications.index') }}"
                class="inline-flex items-center px-4 py-2 text-white rounded-lg font-semibold"
                style="background:#8B5CF6; transition: background 0.2s, transform 0.15s, box-shadow 0.2s;"
                onmouseenter="this.style.background='#7C3AED'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 14px rgba(139,92,246,0.35)';"
                onmouseleave="this.style.background='#8B5CF6'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                Notifications
                @php
                    $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count();
                @endphp
                @if($unreadCount > 0)
                    <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                        {{ $unreadCount }}
                    </span>
                @endif
            </a>
        </div>

        {{-- Emergency Alert --}}
        <div class="mb-6 p-4 bg-white rounded-xl border-2 border-red-400"
            style="transition: transform 0.2s, box-shadow 0.2s;"
            onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(226,75,74,0.15)';"
            onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
            <div class="flex items-center mb-3">
                <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <h4 class="text-red-600 font-bold text-lg">Active Emergency Alerts</h4>
            </div>

            @php
                $alerts = \App\Models\EmergencyAlert::where('status', 'pending')->get();
            @endphp

            @if($alerts->count() > 0)
                @foreach($alerts as $alert)
                    <div class="flex justify-between items-center bg-red-50 p-3 rounded-lg mb-2 border border-red-200"
                        style="transition: transform 0.2s;"
                        onmouseenter="this.style.transform='translateX(4px)';"
                        onmouseleave="this.style.transform='translateX(0)';">
                        <p class="text-red-700 font-medium">Driver #{{ $alert->driver_id }} is asking for help!</p>
                        <form action="{{ route('admin.emergency.resolve', $alert->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="text-white px-4 py-1.5 rounded-md text-sm font-bold"
                                style="background:#1D9E75; transition: background 0.2s, transform 0.15s;"
                                onmouseenter="this.style.background='#0F6E56'; this.style.transform='scale(1.05)';"
                                onmouseleave="this.style.background='#1D9E75'; this.style.transform='scale(1)';">
                                RESOLVE
                            </button>
                        </form>
                    </div>
                @endforeach
            @else
                <p class="text-gray-400 text-sm italic">No active emergencies at the moment.</p>
            @endif
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

            {{-- Active E-Jeeps --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6 cursor-default"
                style="transition: transform 0.2s cubic-bezier(.4,0,.2,1), box-shadow 0.2s;"
                onmouseenter="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 24px rgba(18,25,107,0.12)'; this.querySelector('.stat-icon').style.transform='scale(1.15) rotate(-6deg)';"
                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.07)'; this.querySelector('.stat-icon').style.transform='scale(1) rotate(0)';">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Active E-Jeeps</p>
                        <p id="active-ejeeps" class="text-3xl font-bold mt-2" style="color:#12196B;">{{ $activeEjeeps }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $maintenanceEjeeps }} in maintenance</p>
                    </div>
                    <div class="stat-icon rounded-full p-3" style="background:#E6F1FB; transition: transform 0.25s cubic-bezier(.4,0,.2,1);">
                        <svg class="w-8 h-8" style="color:#185FA5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Drivers on Trip --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6 cursor-default"
                style="transition: transform 0.2s cubic-bezier(.4,0,.2,1), box-shadow 0.2s;"
                onmouseenter="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 24px rgba(18,25,107,0.12)'; this.querySelector('.stat-icon').style.transform='scale(1.15) rotate(-6deg)';"
                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.07)'; this.querySelector('.stat-icon').style.transform='scale(1) rotate(0)';">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Drivers on Trip</p>
                        <p id="drivers-on-trip" class="text-3xl font-bold mt-2" style="color:#12196B;">{{ $driversOnTrip }}</p>
                        <p class="text-xs text-gray-400 mt-1">of {{ $totalDrivers }} total drivers</p>
                    </div>
                    <div class="stat-icon rounded-full p-3" style="background:#E1F5EE; transition: transform 0.25s cubic-bezier(.4,0,.2,1);">
                        <svg class="w-8 h-8" style="color:#0F6E56;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Ongoing Trips --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6 cursor-default"
                style="transition: transform 0.2s cubic-bezier(.4,0,.2,1), box-shadow 0.2s;"
                onmouseenter="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 24px rgba(18,25,107,0.12)'; this.querySelector('.stat-icon').style.transform='scale(1.15) rotate(-6deg)';"
                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.07)'; this.querySelector('.stat-icon').style.transform='scale(1) rotate(0)';">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Ongoing Trips</p>
                        <p id="ongoing-trips" class="text-3xl font-bold mt-2" style="color:#12196B;">{{ $ongoingTrips }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $scheduledTrips }} scheduled</p>
                    </div>
                    <div class="stat-icon rounded-full p-3" style="background:#FAEEDA; transition: transform 0.25s cubic-bezier(.4,0,.2,1);">
                        <svg class="w-8 h-8" style="color:#854F0B;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Completed Today --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6 cursor-default"
                style="transition: transform 0.2s cubic-bezier(.4,0,.2,1), box-shadow 0.2s;"
                onmouseenter="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 24px rgba(18,25,107,0.12)'; this.querySelector('.stat-icon').style.transform='scale(1.15) rotate(-6deg)';"
                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.07)'; this.querySelector('.stat-icon').style.transform='scale(1) rotate(0)';">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Completed Today</p>
                        <p class="text-3xl font-bold mt-2" style="color:#12196B;">{{ $completedToday }}</p>
                        <p class="text-xs text-gray-400 mt-1">trips finished</p>
                    </div>
                    <div class="stat-icon rounded-full p-3" style="background:#EEEDFE; transition: transform 0.25s cubic-bezier(.4,0,.2,1);">
                        <svg class="w-8 h-8" style="color:#534AB7;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Capacity & Route Alerts --}}
        @if($capacityAlerts->count() > 0 || $routeDeviations->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            @if($capacityAlerts->count() > 0)
            <div class="bg-white rounded-xl border border-gray-100"
                style="transition: transform 0.2s, box-shadow 0.2s;"
                onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(18,25,107,0.09)';"
                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h2 class="text-lg font-semibold text-gray-800">Capacity Alerts</h2>
                        <span id="capacity-alerts-badge" class="ml-auto text-xs font-medium px-2.5 py-0.5 rounded-full" style="background:#FAEEDA; color:#854F0B;">{{ $capacityAlerts->count() }}</span>
                    </div>
                </div>
                <div id="capacity-alerts-container" class="p-6">
                    <div class="space-y-4">
                        @foreach($capacityAlerts as $trip)
                        <div class="flex items-start p-4 rounded-lg border"
                            style="background:#FFF7F0; border-color:#FAC775; transition: transform 0.2s;"
                            onmouseenter="this.style.transform='translateX(4px)';"
                            onmouseleave="this.style.transform='translateX(0)';">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">{{ $trip->ejeep->vehicle_number }} - {{ $trip->route->route_name }}</p>
                                <p class="text-sm text-gray-500 mt-1">Driver: {{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</p>
                                <div class="mt-2 flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-red-700">
                                        {{ $trip->current_passenger_count }}/{{ $trip->ejeep->passenger_capacity }} passengers
                                    </span>
                                    @if($trip->current_passenger_count > $trip->ejeep->passenger_capacity)
                                        <span class="text-xs px-2 py-0.5 rounded font-medium" style="background:#FCEBEB; color:#A32D2D;">OVER CAPACITY</span>
                                    @else
                                        <span class="text-xs px-2 py-0.5 rounded font-medium" style="background:#FAEEDA; color:#854F0B;">AT CAPACITY</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($routeDeviations->count() > 0)
            <div class="bg-white rounded-xl border border-gray-100"
                style="transition: transform 0.2s, box-shadow 0.2s;"
                onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(18,25,107,0.09)';"
                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h2 class="text-lg font-semibold text-gray-800">Route Deviations</h2>
                        <span id="route-deviations-badge" class="ml-auto text-xs font-medium px-2.5 py-0.5 rounded-full" style="background:#FAEEDA; color:#854F0B;">{{ $routeDeviations->count() }}</span>
                    </div>
                </div>
                <div id="route-deviations-container" class="p-6">
                    <div class="space-y-4">
                        @foreach($routeDeviations as $trip)
                        <div class="flex items-start p-4 rounded-lg border"
                            style="background:#FFF8F0; border-color:#F0997B; transition: transform 0.2s;"
                            onmouseenter="this.style.transform='translateX(4px)';"
                            onmouseleave="this.style.transform='translateX(0)';">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">{{ $trip->ejeep->vehicle_number }} - {{ $trip->route->route_name }}</p>
                                <p class="text-sm text-gray-500 mt-1">Driver: {{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</p>
                                @if($trip->deviation_notes)
                                <p class="text-sm text-gray-600 mt-2 italic">"{{ $trip->deviation_notes }}"</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-2">
                                    Status: <span class="font-medium text-gray-600">{{ ucfirst($trip->status) }}</span>
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Active & Recent Trips --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Active Trips --}}
            <div class="bg-white rounded-xl border border-gray-100"
                style="transition: transform 0.2s, box-shadow 0.2s;"
                onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(18,25,107,0.09)';"
                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">Active Trips</h2>
                </div>
                <div class="p-6">
                    @if($activeTrips->count() > 0)
                    <div class="space-y-4">
                        @foreach($activeTrips as $trip)
                        <div class="border border-gray-100 rounded-xl p-4"
                            style="transition: transform 0.2s, border-color 0.2s;"
                            onmouseenter="this.style.transform='translateX(4px)'; this.style.borderColor='#85B7EB';"
                            onmouseleave="this.style.transform='translateX(0)'; this.style.borderColor='#F3F4F6';">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:#E1F5EE; color:#0F6E56;">
                                            In Progress
                                        </span>
                                        <span class="ml-2 text-sm font-semibold" style="color:#185FA5;">{{ $trip->ejeep->vehicle_number }}</span>
                                    </div>
                                    <p class="text-sm font-medium mt-2" style="color:#12196B;">{{ $trip->route->route_name }}</p>
                                    <p class="text-sm text-gray-500">Driver: {{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</p>
                                    <div class="mt-3 flex items-center space-x-4 text-xs text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Started {{ $trip->actual_start_time->diffForHumans() }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            {{ $trip->current_passenger_count }}/{{ $trip->ejeep->passenger_capacity }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-400">No active trips at the moment</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Recent Completed Trips --}}
            <div class="bg-white rounded-xl border border-gray-100"
                style="transition: transform 0.2s, box-shadow 0.2s;"
                onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(18,25,107,0.09)';"
                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Completed Trips</h2>
                </div>
                <div class="p-6">
                    @if($recentTrips->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentTrips as $trip)
                        <div class="border border-gray-100 rounded-xl p-4"
                            style="transition: transform 0.2s, border-color 0.2s;"
                            onmouseenter="this.style.transform='translateX(4px)'; this.style.borderColor='#85B7EB';"
                            onmouseleave="this.style.transform='translateX(0)'; this.style.borderColor='#F3F4F6';">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:#F1EFE8; color:#5F5E5A;">
                                            Completed
                                        </span>
                                        <span class="ml-2 text-sm font-semibold" style="color:#185FA5;">{{ $trip->ejeep->vehicle_number }}</span>
                                    </div>
                                    <p class="text-sm font-medium mt-2" style="color:#12196B;">{{ $trip->route->route_name }}</p>
                                    <p class="text-sm text-gray-500">Driver: {{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</p>
                                    <div class="mt-3 flex items-center space-x-4 text-xs text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $trip->getDuration() }} min
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            Max: {{ $trip->max_passenger_count }}
                                        </span>
                                        <span>{{ $trip->actual_end_time->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-400">No completed trips yet</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    let pollingInterval;

    function updateDashboard() {
        fetch(`{{ route('admin.dashboard.realtime') }}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            updateMetricCard('active-ejeeps', data.activeEjeeps);
            updateMetricCard('drivers-on-trip', data.driversOnTrip);
            updateMetricCard('ongoing-trips', data.ongoingTrips);
            updateCapacityAlerts(data.capacityAlerts);
            updateRouteDeviations(data.routeDeviations);
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
    }

    function updateMetricCard(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }

    function hoverStyles() {
        return 'transition: transform 0.2s; cursor: default;';
    }

    function updateCapacityAlerts(alerts) {
        const container = document.getElementById('capacity-alerts-container');
        if (!container) return;

        if (alerts.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-400">No capacity alerts</p>
                </div>`;
            return;
        }

        let html = '<div class="space-y-4">';
        alerts.forEach(trip => {
            const badge = trip.is_over_capacity
                ? '<span class="text-xs px-2 py-0.5 rounded font-medium" style="background:#FCEBEB;color:#A32D2D;">OVER CAPACITY</span>'
                : '<span class="text-xs px-2 py-0.5 rounded font-medium" style="background:#FAEEDA;color:#854F0B;">AT CAPACITY</span>';

            html += `
                <div class="flex items-start p-4 rounded-lg border" style="background:#FFF7F0;border-color:#FAC775;transition:transform 0.2s;"
                    onmouseenter="this.style.transform='translateX(4px)';" onmouseleave="this.style.transform='translateX(0)';">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">${trip.vehicle_number} - ${trip.route_name}</p>
                        <p class="text-sm text-gray-500 mt-1">Driver: ${trip.driver_name}</p>
                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-medium text-red-700">${trip.current_passenger_count}/${trip.passenger_capacity} passengers</span>
                            ${badge}
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;

        const badge = document.getElementById('capacity-alerts-badge');
        if (badge) badge.textContent = alerts.length;
    }

    function updateRouteDeviations(deviations) {
        const container = document.getElementById('route-deviations-container');
        if (!container) return;

        if (deviations.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-400">No route deviations</p>
                </div>`;
            return;
        }

        let html = '<div class="space-y-4">';
        deviations.forEach(trip => {
            const notes = trip.deviation_notes
                ? `<p class="text-sm text-gray-600 mt-2 italic">"${trip.deviation_notes}"</p>` : '';
            const status = trip.status.charAt(0).toUpperCase() + trip.status.slice(1).replace('_', ' ');

            html += `
                <div class="flex items-start p-4 rounded-lg border" style="background:#FFF8F0;border-color:#F0997B;transition:transform 0.2s;"
                    onmouseenter="this.style.transform='translateX(4px)';" onmouseleave="this.style.transform='translateX(0)';">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">${trip.vehicle_number} - ${trip.route_name}</p>
                        <p class="text-sm text-gray-500 mt-1">Driver: ${trip.driver_name}</p>
                        ${notes}
                        <p class="text-xs text-gray-400 mt-2">Status: <span class="font-medium text-gray-600">${status}</span></p>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;

        const badge = document.getElementById('route-deviations-badge');
        if (badge) badge.textContent = deviations.length;
    }

    function startPolling() {
        stopPolling();
        updateDashboard();
        pollingInterval = setInterval(updateDashboard, 5000);
    }

    function stopPolling() {
        clearInterval(pollingInterval);
    }

    document.addEventListener('DOMContentLoaded', startPolling);

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stopPolling();
        else startPolling();
    });

    window.addEventListener('beforeunload', stopPolling);
</script>
@endpush
@endsection