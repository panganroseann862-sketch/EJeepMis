@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Add New E-Jeep</h2>
                    <a href="{{ route('admin.ejeeps.index') }}" class="text-gray-600 hover:text-gray-900">
                        &larr; Back to List
                    </a>
                </div>

                <form action="{{ route('admin.ejeeps.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="vehicle_number" class="block text-sm font-medium text-gray-700 mb-2">Vehicle Number</label>
                        <input type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number') }}" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('vehicle_number') border-red-500 @enderror">
                        @error('vehicle_number')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="plate_number" class="block text-sm font-medium text-gray-700 mb-2">Plate Number</label>
                        <input type="text" name="plate_number" id="plate_number" value="{{ old('plate_number') }}" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('plate_number') border-red-500 @enderror">
                        @error('plate_number')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="passenger_capacity" class="block text-sm font-medium text-gray-700 mb-2">Passenger Capacity</label>
                        <input type="number" name="passenger_capacity" id="passenger_capacity" value="{{ old('passenger_capacity') }}" min="1" max="100"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('passenger_capacity') border-red-500 @enderror">
                        @error('passenger_capacity')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="operational_status" class="block text-sm font-medium text-gray-700 mb-2">Operational Status</label>
                        <select name="operational_status" id="operational_status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('operational_status') border-red-500 @enderror">
                            <option value="active" {{ old('operational_status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="maintenance" {{ old('operational_status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="inactive" {{ old('operational_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('operational_status')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="last_maintenance_date" class="block text-sm font-medium text-gray-700 mb-2">Last Maintenance Date</label>
                        <input type="date" name="last_maintenance_date" id="last_maintenance_date" value="{{ old('last_maintenance_date') }}" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('last_maintenance_date') border-red-500 @enderror">
                        @error('last_maintenance_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="maintenance_notes" class="block text-sm font-medium text-gray-700 mb-2">Maintenance Notes</label>
                        <textarea name="maintenance_notes" id="maintenance_notes" rows="3" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('maintenance_notes') border-red-500 @enderror">{{ old('maintenance_notes') }}</textarea>
                        @error('maintenance_notes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('admin.ejeeps.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-700">
                            Create E-Jeep
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
