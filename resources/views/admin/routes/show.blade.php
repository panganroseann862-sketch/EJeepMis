@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Route Details</h2>
                    <div class="space-x-2">
                        <a href="{{ route('admin.routes.edit', $route) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                            Edit
                        </a>
                        <a href="{{ route('admin.routes.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
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
                        <h3 class="text-sm font-medium text-gray-500">Route Code</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ $route->route_code }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Route Name</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ $route->route_name }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                        <p class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($route->status === 'active') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($route->status) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Total Stops</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ $route->stops->count() }}</p>
                    </div>
                </div>

                @if($route->description)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500">Description</h3>
                        <p class="mt-1 text-gray-900">{{ $route->description }}</p>
                    </div>
                @endif

                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Stops (in sequence order)</h3>
                    @if($route->stops->count() > 0)
                        <div class="space-y-3">
                            @foreach($route->stops as $stop)
                                <div class="border border-gray-300 rounded p-4 bg-gray-50">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-500 text-white font-semibold">
                                                {{ $stop->sequence_order }}
                                            </span>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <h4 class="text-lg font-medium text-gray-900">{{ $stop->stop_name }}</h4>
                                            @if($stop->location_description)
                                                <p class="mt-1 text-sm text-gray-600">{{ $stop->location_description }}</p>
                                            @endif
                                            @if($stop->latitude && $stop->longitude)
                                                <p class="mt-1 text-xs text-gray-500">
                                                    Coordinates: {{ $stop->latitude }}, {{ $stop->longitude }}
                                                </p>
                                            @endif
                                        </div>
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
