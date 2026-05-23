@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Edit Route</h2>

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.routes.update', $route) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="route_code" class="block text-sm font-medium text-gray-700">Route Code</label>
                        <input type="text" name="route_code" id="route_code" value="{{ old('route_code', $route->route_code) }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    <div class="mb-4">
                        <label for="route_name" class="block text-sm font-medium text-gray-700">Route Name</label>
                        <input type="text" name="route_name" id="route_name" value="{{ old('route_name', $route->route_name) }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $route->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="active" {{ old('status', $route->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $route->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium text-gray-700">Stops</label>
                            <button type="button" onclick="addStop()" class="bg-green-500 hover:bg-green-700 text-white text-sm font-bold py-1 px-3 rounded">
                                Add Stop
                            </button>
                        </div>
                        <div id="stops-container" class="space-y-3">
                            @foreach($route->stops as $index => $stop)
                                <div class="border border-gray-300 rounded p-4 bg-gray-50">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-medium text-gray-700">Stop {{ $index + 1 }}</h4>
                                        <button type="button" onclick="this.closest('div').parentElement.remove()" class="text-red-600 hover:text-red-900 text-sm">Remove</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Stop Name</label>
                                            <input type="text" name="stops[{{ $index }}][stop_name]" value="{{ $stop->stop_name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Location Description</label>
                                            <input type="text" name="stops[{{ $index }}][location_description]" value="{{ $stop->location_description }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Latitude</label>
                                            <input type="number" step="0.00000001" name="stops[{{ $index }}][latitude]" value="{{ $stop->latitude }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Longitude</label>
                                            <input type="number" step="0.00000001" name="stops[{{ $index }}][longitude]" value="{{ $stop->longitude }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('admin.routes.show', $route) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Update Route
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let stopCount = {{ $route->stops->count() }};

function addStop() {
    const container = document.getElementById('stops-container');
    const stopDiv = document.createElement('div');
    stopDiv.className = 'border border-gray-300 rounded p-4 bg-gray-50';
    stopDiv.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <h4 class="font-medium text-gray-700">Stop ${stopCount + 1}</h4>
            <button type="button" onclick="this.closest('div').parentElement.remove()" class="text-red-600 hover:text-red-900 text-sm">Remove</button>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Stop Name</label>
                <input type="text" name="stops[${stopCount}][stop_name]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Location Description</label>
                <input type="text" name="stops[${stopCount}][location_description]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Latitude</label>
                <input type="number" step="0.00000001" name="stops[${stopCount}][latitude]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Longitude</label>
                <input type="number" step="0.00000001" name="stops[${stopCount}][longitude]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    `;
    container.appendChild(stopDiv);
    stopCount++;
}
</script>
@endsection
