@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- Page Header --}}
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Schedule Management</h2>
                <p class="text-sm text-gray-500 mt-1">Manage your e-jeep schedules and trips.</p>
            </div>
            <a href="{{ route('admin.schedules.create') }}"
               class="inline-flex items-center gap-2 bg-blue-800 hover:bg-blue-900 text-white font-medium py-2 px-4 rounded-lg text-sm transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Schedule
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Table Card --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-Jeep</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departure</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($schedules as $schedule)
                            <tr class="hover:bg-gray-50 transition">

                                {{-- Route with icon --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $schedule->route->route_code }}</p>
                                            <p class="text-xs text-gray-500">{{ $schedule->route->route_name }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $schedule->ejeep->vehicle_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $schedule->driver->first_name }} {{ $schedule->driver->last_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ucfirst($schedule->day_of_week) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $schedule->departure_time->format('H:i') }}
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($schedule->status === 'active')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
                                            {{ ucfirst($schedule->status) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.schedules.show', $schedule) }}"
                                           class="inline-flex items-center gap-1 text-xs font-medium text-blue-700 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </a>
                                        <a href="{{ route('admin.schedules.edit', $schedule) }}"
                                           class="inline-flex items-center gap-1 text-xs font-medium text-green-700 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.schedules.destroy', $schedule) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm('Are you sure you want to delete this schedule?')"
                                                    class="inline-flex items-center gap-1 text-xs font-medium text-red-700 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-400">No schedules found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($schedules->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection