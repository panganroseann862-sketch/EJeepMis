@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- Page Header --}}
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Trip Monitoring</h2>
                <p class="text-sm text-gray-500 mt-1">Monitor all trips and their current status.</p>
            </div>
        </div>

        {{-- Filters Card --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('admin.trips.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="status" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-lg border-gray-200 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="scheduled"   {{ request('status') === 'scheduled'   ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="paused"      {{ request('status') === 'paused'      ? 'selected' : '' }}>Paused</option>
                        <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled"   {{ request('status') === 'cancelled'   ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label for="date" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Date</label>
                    <input type="date" name="date" id="date" value="{{ request('date') }}"
                           class="w-full rounded-lg border-gray-200 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="driver_id" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Driver</label>
                    <select name="driver_id" id="driver_id" class="w-full rounded-lg border-gray-200 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Drivers</option>
                        @foreach(\App\Models\User::drivers()->get() as $driver)
                            <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->first_name }} {{ $driver->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="route_id" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Route</label>
                    <select name="route_id" id="route_id" class="w-full rounded-lg border-gray-200 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Routes</option>
                        @foreach(\App\Models\Route::active()->get() as $route)
                            <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>
                                {{ $route->route_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-4 flex gap-2 pt-1">
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-blue-800 hover:bg-blue-900 text-white font-medium py-2 px-4 rounded-lg text-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.trips.index') }}"
                       class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>

        {{-- Trips Table Card --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trip ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-Jeep</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passengers</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100" id="trips-table-body">
                        @forelse($trips as $trip)
                            <tr class="hover:bg-gray-50 transition" data-trip-id="{{ $trip->id }}">

                                {{-- Trip ID --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-700">#{{ $trip->id }}</span>
                                </td>

                                {{-- E-Jeep --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $trip->ejeep->vehicle_number }}
                                </td>

                                {{-- Driver --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $trip->driver->first_name }} {{ $trip->driver->last_name }}
                                </td>

                                {{-- Route --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-md bg-teal-50 flex items-center justify-center flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm text-gray-700">{{ $trip->route->route_name }}</span>
                                    </div>
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusConfig = [
                                            'scheduled'   => ['bg-gray-100 text-gray-700',   'bg-gray-400'],
                                            'in_progress' => ['bg-blue-100 text-blue-800',   'bg-blue-500'],
                                            'paused'      => ['bg-yellow-100 text-yellow-800','bg-yellow-500'],
                                            'completed'   => ['bg-green-100 text-green-800', 'bg-green-500'],
                                            'cancelled'   => ['bg-red-100 text-red-800',     'bg-red-500'],
                                        ];
                                        [$badgeClass, $dotClass] = $statusConfig[$trip->status] ?? ['bg-gray-100 text-gray-700', 'bg-gray-400'];
                                    @endphp
                                    <span class="status-badge inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium {{ $badgeClass }}"
                                          data-status="{{ $trip->status }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }} inline-block"></span>
                                        {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                    </span>
                                </td>

                                {{-- Passengers --}}
                                <td class="px-6 py-4 whitespace-nowrap passenger-count">
                                    <span class="text-sm {{ $trip->isOverCapacity() ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                        {{ $trip->current_passenger_count }} / {{ $trip->ejeep->passenger_capacity }}
                                    </span>
                                    @if($trip->isOverCapacity())
                                        <span class="ml-1 text-red-500 text-xs font-bold">⚠</span>
                                    @endif
                                </td>

                                {{-- Scheduled Time --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $trip->scheduled_start_time->format('M d, Y h:i A') }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.trips.show', $trip) }}"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-blue-700 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View Details
                                    </a>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-400">
                                    No trips found matching the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($trips->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $trips->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script>
    let pollingInterval;

    function updateActiveTrips() {
        const statusFilter = '{{ request('status') }}';
        if (statusFilter && statusFilter !== 'in_progress' && statusFilter !== 'paused') return;

        fetch('{{ route('admin.trips.active') }}', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            const statusConfig = {
                'in_progress': ['bg-blue-100 text-blue-800', 'bg-blue-500'],
                'paused':      ['bg-yellow-100 text-yellow-800', 'bg-yellow-500'],
                'completed':   ['bg-green-100 text-green-800', 'bg-green-500'],
            };

            data.forEach(trip => {
                const row = document.querySelector(`tr[data-trip-id="${trip.id}"]`);
                if (!row) return;

                const passengerCell = row.querySelector('.passenger-count');
                if (passengerCell) {
                    passengerCell.innerHTML = `
                        <span class="text-sm ${trip.is_over_capacity ? 'text-red-600 font-semibold' : 'text-gray-500'}">
                            ${trip.current_passenger_count} / ${trip.capacity}
                        </span>
                        ${trip.is_over_capacity ? '<span class="ml-1 text-red-500 text-xs font-bold">⚠</span>' : ''}
                    `;
                }

                const badge = row.querySelector('.status-badge');
                if (badge && badge.dataset.status !== trip.status) {
                    const [badgeClass, dotClass] = statusConfig[trip.status] || ['bg-gray-100 text-gray-700', 'bg-gray-400'];
                    badge.className = `status-badge inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium ${badgeClass}`;
                    badge.dataset.status = trip.status;
                    badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full ${dotClass} inline-block"></span>
                        ${trip.status.charAt(0).toUpperCase() + trip.status.slice(1).replace('_', ' ')}`;
                }
            });
        })
        .catch(err => console.error('Error fetching active trips:', err));
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateActiveTrips();
        pollingInterval = setInterval(updateActiveTrips, 5000);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            clearInterval(pollingInterval);
        } else {
            updateActiveTrips();
            pollingInterval = setInterval(updateActiveTrips, 5000);
        }
    });

    window.addEventListener('beforeunload', function () {
        clearInterval(pollingInterval);
    });
</script>
@endpush
@endsection