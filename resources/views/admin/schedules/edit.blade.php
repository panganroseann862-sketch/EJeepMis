@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Edit Schedule</h2>

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="route_id" class="block text-sm font-medium text-gray-700">Route</label>
                        <select name="route_id" id="route_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Select a route</option>
                            @foreach($routes as $route)
                                <option value="{{ $route->id }}" {{ old('route_id', $schedule->route_id) == $route->id ? 'selected' : '' }}>
                                    {{ $route->route_code }} - {{ $route->route_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="ejeep_id" class="block text-sm font-medium text-gray-700">E-Jeep</label>
                        <select name="ejeep_id" id="ejeep_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Select an E-Jeep</option>
                            @foreach($ejeeps as $ejeep)
                                <option value="{{ $ejeep->id }}" {{ old('ejeep_id', $schedule->ejeep_id) == $ejeep->id ? 'selected' : '' }}>
                                    {{ $ejeep->vehicle_number }} ({{ $ejeep->plate_number }}) - Capacity: {{ $ejeep->passenger_capacity }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Note: E-Jeeps under maintenance are excluded from this list.</p>
                    </div>

                    <div class="mb-4">
                        <label for="driver_id" class="block text-sm font-medium text-gray-700">Driver</label>
                        <select name="driver_id" id="driver_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Select a driver</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id', $schedule->driver_id) == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->first_name }} {{ $driver->last_name }} ({{ $driver->username }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="day_of_week" class="block text-sm font-medium text-gray-700">Day of Week</label>
                        <select name="day_of_week" id="day_of_week" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Select a day</option>
                            <option value="monday" {{ old('day_of_week', $schedule->day_of_week) === 'monday' ? 'selected' : '' }}>Monday</option>
                            <option value="tuesday" {{ old('day_of_week', $schedule->day_of_week) === 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="wednesday" {{ old('day_of_week', $schedule->day_of_week) === 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="thursday" {{ old('day_of_week', $schedule->day_of_week) === 'thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="friday" {{ old('day_of_week', $schedule->day_of_week) === 'friday' ? 'selected' : '' }}>Friday</option>
                            <option value="saturday" {{ old('day_of_week', $schedule->day_of_week) === 'saturday' ? 'selected' : '' }}>Saturday</option>
                            <option value="sunday" {{ old('day_of_week', $schedule->day_of_week) === 'sunday' ? 'selected' : '' }}>Sunday</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="departure_time" class="block text-sm font-medium text-gray-700">Departure Time</label>
                        <input type="time" name="departure_time" id="departure_time" value="{{ old('departure_time', $schedule->departure_time->format('H:i')) }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="active" {{ old('status', $schedule->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="cancelled" {{ old('status', $schedule->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('admin.schedules.show', $schedule) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Update Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
