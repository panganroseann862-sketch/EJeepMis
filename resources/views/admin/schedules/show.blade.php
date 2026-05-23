@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Schedule Details</h2>
                    <div class="space-x-2">
                        <a href="{{ route('admin.schedules.edit', $schedule) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                            Edit
                        </a>
                        <a href="{{ route('admin.schedules.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Back to List
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Route</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ $schedule->route->route_code }} - {{ $schedule->route->route_name }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">E-Jeep</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ $schedule->ejeep->vehicle_number }} ({{ $schedule->ejeep->plate_number }})</p>
                        <p class="text-sm text-gray-600">Capacity: {{ $schedule->ejeep->passenger_capacity }} passengers</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Driver</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ $schedule->driver->first_name }} {{ $schedule->driver->last_name }}</p>
                        <p class="text-sm text-gray-600">{{ $schedule->driver->username }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Day of Week</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ ucfirst($schedule->day_of_week) }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Departure Time</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ $schedule->departure_time->format('H:i') }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                        <p class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($schedule->status === 'active') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($schedule->status) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Route Stops</h3>
                    @if($schedule->route->stops->count() > 0)
                        <div class="space-y-2">
                            @foreach($schedule->route->getOrderedStops() as $stop)
                                <div class="flex items-center p-3 bg-gray-50 rounded">
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-500 text-white text-xs font-semibold mr-3">
                                        {{ $stop->sequence_order }}
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $stop->stop_name }}</p>
                                        @if($stop->location_description)
                                            <p class="text-sm text-gray-600">{{ $stop->location_description }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No stops defined for this route.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
