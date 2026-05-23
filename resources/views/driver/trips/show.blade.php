@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Trip Details</h1>
                    <p class="text-gray-600 mt-1">{{ $trip->route->route_name }} - {{ $trip->ejeep->vehicle_number }}</p>
                </div>
                <div>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                        @if($trip->status === 'scheduled') bg-yellow-100 text-yellow-800
                        @elseif($trip->status === 'in_progress') bg-green-100 text-green-800
                        @elseif($trip->status === 'paused') bg-orange-100 text-orange-800
                        @elseif($trip->status === 'completed') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Trip Control Buttons -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Trip Controls</h2>
            <div class="flex flex-wrap gap-3">
                @if($trip->status === 'scheduled')
                    <button id="startTripBtn" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Start Trip
                    </button>
                @elseif($trip->status === 'in_progress')
                    <button id="pauseTripBtn" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Pause Trip
                    </button>
                    <button id="completeTripBtn" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Complete Trip
                    </button>
                @elseif($trip->status === 'paused')
                    <button id="startTripBtn" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Resume Trip
                    </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Passenger Capacity Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-600 mb-2">Current Passengers</h3>
                <div class="flex items-baseline">
                    <p class="text-4xl font-bold text-gray-900">{{ $trip->current_passenger_count }}</p>
                    <p class="text-xl text-gray-500 ml-2">/ {{ $trip->ejeep->passenger_capacity }}</p>
                </div>
                @if($trip->current_passenger_count > $trip->ejeep->passenger_capacity)
                    <div class="mt-3 flex items-center text-red-600">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-semibold">OVER CAPACITY!</span>
                    </div>
                @elseif($trip->current_passenger_count === $trip->ejeep->passenger_capacity)
                    <div class="mt-3 flex items-center text-yellow-600">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-semibold">AT CAPACITY</span>
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-600">{{ $remainingCapacity }} seats available</p>
                @endif
            </div>

            <!-- Next Stop Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-600 mb-2">Next Stop</h3>
                @if($nextStop)
                    <p class="text-2xl font-bold text-gray-900">{{ $nextStop->stop_name }}</p>
                    <p class="text-sm text-gray-600 mt-2">Stop {{ $nextStop->sequence_order }} of {{ $trip->route->stops->count() }}</p>
                @else
                    <p class="text-2xl font-bold text-gray-900">Route Complete</p>
                    <p class="text-sm text-gray-600 mt-2">All stops visited</p>
                @endif
            </div>

            <!-- Trip Progress Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-600 mb-2">Route Progress</h3>
                <div class="flex items-baseline">
                    <p class="text-4xl font-bold text-gray-900">{{ $trip->passengerLogs->count() }}</p>
                    <p class="text-xl text-gray-500 ml-2">/ {{ $trip->route->stops->count() }}</p>
                </div>
                <p class="text-sm text-gray-600 mt-2">stops completed</p>
                <div class="mt-3 w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $trip->route->stops->count() > 0 ? ($trip->passengerLogs->count() / $trip->route->stops->count() * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        <!-- Route with Stops -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Route & Stops</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($trip->route->stops as $stop)
                        @php
                            $passengerLog = $trip->passengerLogs->firstWhere('stop_id', $stop->id);
                            $isCompleted = $passengerLog !== null;
                            $isNext = $nextStop && $nextStop->id === $stop->id;
                        @endphp
                        <div class="flex items-start {{ $isNext ? 'bg-blue-50 border-2 border-blue-500' : 'border border-gray-200' }} rounded-lg p-4">
                            <div class="flex-shrink-0">
                                @if($isCompleted)
                                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                @elseif($isNext)
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold">{{ $stop->sequence_order }}</span>
                                    </div>
                                @else
                                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                        <span class="text-gray-600 font-bold">{{ $stop->sequence_order }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $stop->stop_name }}</h3>
                                        @if($stop->location_description)
                                            <p class="text-sm text-gray-600">{{ $stop->location_description }}</p>
                                        @endif
                                        @if($isCompleted && $passengerLog)
                                            <div class="mt-2 text-sm text-gray-600">
                                                <span class="font-medium">Passengers:</span> {{ $passengerLog->passenger_count }}
                                                <span class="mx-2">|</span>
                                                <span class="text-green-600">+{{ $passengerLog->boarding_count }} boarded</span>
                                                <span class="mx-2">|</span>
                                                <span class="text-red-600">-{{ $passengerLog->alighting_count }} alighted</span>
                                            </div>
                                        @endif
                                    </div>
                                    @if($isNext && $trip->status === 'in_progress')
                                        <button onclick="openPassengerModal({{ $stop->id }}, '{{ $stop->stop_name }}')" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                                            Record Passengers
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Trip Information -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Trip Information</h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-600">Vehicle</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->ejeep->vehicle_number }} ({{ $trip->ejeep->plate_number }})</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600">Route</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->route->route_name }} ({{ $trip->route->route_code }})</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600">Scheduled Start</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->scheduled_start_time->format('M d, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600">Actual Start</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->actual_start_time ? $trip->actual_start_time->format('M d, Y g:i A') : 'Not started' }}</dd>
                    </div>
                    @if($trip->actual_end_time)
                    <div>
                        <dt class="text-sm font-medium text-gray-600">Completed At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->actual_end_time->format('M d, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600">Duration</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->getDuration() }} minutes</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-600">Max Passengers</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->max_passenger_count }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Passenger Count Modal -->
<div id="passengerModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Record Passenger Count</h3>
            <p class="text-sm text-gray-600 mb-4">Stop: <span id="modalStopName" class="font-semibold"></span></p>
            
            <form id="passengerForm">
                <input type="hidden" id="stopId" name="stop_id">
                
                <div class="mb-4">
                    <label for="passengerCount" class="block text-sm font-medium text-gray-700 mb-2">Current Passenger Count</label>
                    <input type="number" id="passengerCount" name="passenger_count" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="boardingCount" class="block text-sm font-medium text-gray-700 mb-2">Passengers Boarding</label>
                    <input type="number" id="boardingCount" name="boarding_count" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="alightingCount" class="block text-sm font-medium text-gray-700 mb-2">Passengers Alighting</label>
                    <input type="number" id="alightingCount" name="alighting_count" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div id="capacityWarning" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <div class="flex items-center text-red-800">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-semibold">Warning: Exceeds vehicle capacity!</span>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closePassengerModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const tripId = {{ $trip->id }};
const vehicleCapacity = {{ $trip->ejeep->passenger_capacity }};
const csrfToken = '{{ csrf_token() }}';

// Trip control functions
document.getElementById('startTripBtn')?.addEventListener('click', async function() {
    if (!confirm('Are you sure you want to start this trip?')) return;
    
    try {
        const response = await fetch(`/driver/trips/${tripId}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
});

document.getElementById('pauseTripBtn')?.addEventListener('click', async function() {
    if (!confirm('Are you sure you want to pause this trip?')) return;
    
    try {
        const response = await fetch(`/driver/trips/${tripId}/pause`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
});

document.getElementById('completeTripBtn')?.addEventListener('click', async function() {
    if (!confirm('Are you sure you want to complete this trip?')) return;
    
    try {
        const response = await fetch(`/driver/trips/${tripId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
});

// Passenger modal functions
function openPassengerModal(stopId, stopName) {
    document.getElementById('stopId').value = stopId;
    document.getElementById('modalStopName').textContent = stopName;
    document.getElementById('passengerModal').classList.remove('hidden');
}

function closePassengerModal() {
    document.getElementById('passengerModal').classList.add('hidden');
    document.getElementById('passengerForm').reset();
    document.getElementById('capacityWarning').classList.add('hidden');
}

// Check capacity warning
document.getElementById('passengerCount')?.addEventListener('input', function() {
    const count = parseInt(this.value) || 0;
    const warning = document.getElementById('capacityWarning');
    
    if (count > vehicleCapacity) {
        warning.classList.remove('hidden');
    } else {
        warning.classList.add('hidden');
    }
});

// Handle passenger form submission
document.getElementById('passengerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        stop_id: document.getElementById('stopId').value,
        passenger_count: parseInt(document.getElementById('passengerCount').value),
        boarding_count: parseInt(document.getElementById('boardingCount').value),
        alighting_count: parseInt(document.getElementById('alightingCount').value)
    };
    
    try {
        const response = await fetch(`/driver/trips/${tripId}/passenger-count`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.data.warning) {
                alert(data.data.warning);
            } else {
                alert(data.message);
            }
            closePassengerModal();
            window.location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
});
</script>
@endsection

