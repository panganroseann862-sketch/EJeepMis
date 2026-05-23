@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Driver Details</h2>
                    <div class="space-x-2">
                        <a href="{{ route('admin.drivers.edit', $driver) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Edit Driver
                        </a>
                        <a href="{{ route('admin.drivers.index') }}" class="text-gray-600 hover:text-gray-900">
                            Back to Drivers
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Personal Information</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-600 font-medium">Name:</span>
                                <span class="text-gray-900">{{ $driver->first_name }} {{ $driver->last_name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Username:</span>
                                <span class="text-gray-900">{{ $driver->username }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Email:</span>
                                <span class="text-gray-900">{{ $driver->email }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Phone:</span>
                                <span class="text-gray-900">{{ $driver->phone ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Status:</span>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($driver->status === 'active') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($driver->status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Performance Metrics</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-600 font-medium">Completed Trips:</span>
                                <span class="text-gray-900">{{ $totalTrips }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Schedule Adherence:</span>
                                <span class="text-gray-900">{{ $scheduleAdherence }}%</span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Average Passenger Load:</span>
                                <span class="text-gray-900">{{ number_format($avgPassengerLoad, 1) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Assigned Routes</h3>
                    @if($driver->driverSchedules->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-Jeep</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departure Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($driver->driverSchedules as $schedule)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $schedule->route->route_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $schedule->ejeep->vehicle_number }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ ucfirst($schedule->day_of_week) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $schedule->departure_time->format('h:i A') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @if($schedule->status === 'active') bg-green-100 text-green-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ ucfirst($schedule->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">No routes assigned yet.</p>
                    @endif
                </div>

                <div class="border-t pt-4">
                    <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Are you sure you want to delete this driver? This action cannot be undone.')">
                            Delete Driver
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
