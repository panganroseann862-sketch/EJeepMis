@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">E-Jeep Details</h2>
                    <div class="space-x-3">
                        <a href="{{ route('admin.ejeeps.edit', $ejeep) }}" class="text-indigo-600 hover:text-indigo-900">
                            Edit
                        </a>
                        <a href="{{ route('admin.ejeeps.index') }}" class="text-gray-600 hover:text-gray-900">
                            &larr; Back to List
                        </a>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Vehicle Number</label>
                        <p class="mt-1 text-lg text-gray-900">{{ $ejeep->vehicle_number }}</p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Plate Number</label>
                        <p class="mt-1 text-lg text-gray-900">{{ $ejeep->plate_number }}</p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Passenger Capacity</label>
                        <p class="mt-1 text-lg text-gray-900">{{ $ejeep->passenger_capacity }} passengers</p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Operational Status</label>
                        <p class="mt-1">
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                @if($ejeep->operational_status === 'active') bg-green-100 text-green-800
                                @elseif($ejeep->operational_status === 'maintenance') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($ejeep->operational_status) }}
                            </span>
                        </p>
                    </div>

                    @if($ejeep->last_maintenance_date)
                        <div class="border-b pb-3">
                            <label class="block text-sm font-medium text-gray-500">Last Maintenance Date</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $ejeep->last_maintenance_date->format('F d, Y') }}</p>
                        </div>
                    @endif

                    @if($ejeep->maintenance_notes)
                        <div class="border-b pb-3">
                            <label class="block text-sm font-medium text-gray-500">Maintenance Notes</label>
                            <p class="mt-1 text-gray-900">{{ $ejeep->maintenance_notes }}</p>
                        </div>
                    @endif

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Created At</label>
                        <p class="mt-1 text-gray-900">{{ $ejeep->created_at->format('F d, Y g:i A') }}</p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Last Updated</label>
                        <p class="mt-1 text-gray-900">{{ $ejeep->updated_at->format('F d, Y g:i A') }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <form action="{{ route('admin.ejeeps.destroy', $ejeep) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this E-Jeep?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-700">
                            Delete E-Jeep
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
