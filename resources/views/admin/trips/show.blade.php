@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Trip #{{ $trip->id }}</h1>
            <p class="mt-2 text-sm text-gray-600">Detailed trip information and passenger logs</p>
        </div>
        <a href="{{ route('admin.trips.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
            Back to Trips
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Trip Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Trip Information Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Trip Information</h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Status</p>
                        @php
                            $statusColors = [
                                'scheduled' => 'bg-gray-100 text-gray-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'paused' => 'bg-yellow-100 text-yellow-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <span class="mt-1 px-3 py-1 inline-flex text-sm font-semibold rounded-full {{ $statusColors[$trip->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                        </span>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">E-Jeep</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $trip->ejeep->vehicle_number }} ({{ $trip->ejeep->plate_number }})</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Driver</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $trip->driver->first_name }} {{ $trip->driver->last_name }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Route</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $trip->route->route_name }} ({{ $trip->route->route_code }})</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Scheduled Start</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $trip->scheduled_start_time->format('M d, Y h:i A') }}</p>
                    </div>

                    @if($trip->actual_start_time)
                        <div>
                            <p class="text-sm font-medium text-gray-500">Actual Start</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $trip->actual_start_time->format('M d, Y h:i A') }}</p>
                        </div>
                    @endif

                    @if($trip->actual_end_time)
                        <div>
                            <p class="text-sm font-medium text-gray-500">Actual End</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $trip->actual_end_time->format('M d, Y h:i A') }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">Duration</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $trip->getDuration() }} minutes</p>
                        </div>
                    @endif

                    <div>
                        <p class="text-sm font-medium text-gray-500">Current Passengers</p>
                        <p class="mt-1 text-sm {{ $trip->isOverCapacity() ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                            {{ $trip->current_passenger_count }} / {{ $trip->ejeep->passenger_capacity }}
                            @if($trip->isOverCapacity())
                                <span class="ml-1">⚠️ Over Capacity</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Max Passengers Reached</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $trip->max_passenger_count }}</p>
                    </div>

                    @if($trip->has_route_deviation)
                        <div class="col-span-2">
                            <p class="text-sm font-medium text-red-600">⚠️ Route Deviation Detected</p>
                            @if($trip->deviation_notes)
                                <p class="mt-1 text-sm text-gray-700">{{ $trip->deviation_notes }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Route Progress -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Route Progress</h2>
                
                @if($nextStop)
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm font-medium text-blue-900">Next Stop: {{ $nextStop->stop_name }}</p>
                        @if($eta)
                            <p class="text-sm text-blue-700 mt-1">Estimated Arrival: {{ $eta }}</p>
                        @endif
                    </div>
                @elseif($trip->status === 'completed')
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                        <p class="text-sm font-medium text-green-900">✓ All stops completed</p>
                    </div>
                @endif

                <div class="space-y-3">
                    @foreach($trip->route->stops as $stop)
                        @php
                            $log = $trip->passengerLogs->firstWhere('stop_id', $stop->id);
                            $isVisited = $log !== null;
                            $isNext = $nextStop && $nextStop->id === $stop->id;
                        @endphp
                        
                        <div class="flex items-start {{ $isNext ? 'bg-blue-50 -mx-2 px-2 py-2 rounded' : '' }}">
                            <div class="flex-shrink-0 mt-1">
                                @if($isVisited)
                                    <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                @elseif($isNext)
                                    <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
                                        <div class="w-2 h-2 rounded-full bg-white"></div>
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-300"></div>
                                @endif
                            </div>
                            
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $stop->sequence_order }}. {{ $stop->stop_name }}
                                    @if($isNext)
                                        <span class="ml-2 text-blue-600 font-semibold">(Next)</span>
                                    @endif
                                </p>
                                @if($stop->location_description)
                                    <p class="text-xs text-gray-500 mt-1">{{ $stop->location_description }}</p>
                                @endif
                                
                                @if($log)
                                    <div class="mt-2 text-xs text-gray-600 space-y-1">
                                        <p>Passengers: {{ $log->passenger_count }} (↑{{ $log->boarding_count }} ↓{{ $log->alighting_count }})</p>
                                        <p>Recorded: {{ $log->recorded_at->format('h:i A') }}</p>
                                        @if($log->is_over_capacity)
                                            <p class="text-red-600 font-semibold">⚠️ Over capacity at this stop</p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Passenger Logs Table -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Passenger Logs</h2>
                
                @if($trip->passengerLogs->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stop</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Boarding</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alighting</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($trip->passengerLogs->sortBy('recorded_at') as $log)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $log->stop->stop_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $log->boarding_count }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $log->alighting_count }}</td>
                                        <td class="px-4 py-3 text-sm {{ $log->is_over_capacity ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                            {{ $log->passenger_count }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->recorded_at->format('h:i A') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($log->is_over_capacity)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    Over Capacity
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Normal
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No passenger logs recorded yet.</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500">Stops Completed</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $trip->passengerLogs->count() }} / {{ $trip->route->stops->count() }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Remaining Capacity</p>
                        <p class="text-2xl font-bold {{ $trip->getRemainingCapacity() < 0 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $trip->getRemainingCapacity() }}
                        </p>
                    </div>

                    @if($trip->status === 'in_progress' && $eta)
                        <div>
                            <p class="text-sm text-gray-500">Estimated Completion</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $eta }}</p>
                        </div>
                    @endif

                    @if($trip->status === 'completed' && $trip->getDuration())
                        <div>
                            <p class="text-sm text-gray-500">Total Duration</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $trip->getDuration() }} min</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Alerts -->
            @if($trip->isOverCapacity() || $trip->has_route_deviation)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-red-900 mb-2">⚠️ Alerts</h3>
                    <ul class="space-y-2 text-sm text-red-700">
                        @if($trip->isOverCapacity())
                            <li>• Vehicle is over capacity</li>
                        @endif
                        @if($trip->has_route_deviation)
                            <li>• Route deviation detected</li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Real-time updates for active trips (every 5 seconds)
    @if(in_array($trip->status, ['in_progress', 'paused']))
        setInterval(function() {
            // Reload the page to get updated data
            location.reload();
        }, 5000);
    @endif
</script>
@endsection
