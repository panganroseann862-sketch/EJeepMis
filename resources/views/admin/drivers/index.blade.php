@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold" style="color:#12196B;">Driver Management</h1>
                <p class="text-gray-500 mt-1">Manage your registered e-jeep drivers.</p>
            </div>
            <a href="{{ route('admin.drivers.create') }}"
                class="inline-flex items-center px-4 py-2 text-white rounded-lg font-semibold text-sm"
                style="background:#185FA5; transition: background 0.2s, transform 0.15s, box-shadow 0.2s;"
                onmouseenter="this.style.background='#0C447C'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 14px rgba(24,95,165,0.35)';"
                onmouseleave="this.style.background='#185FA5'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Driver
            </a>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg border text-sm font-medium"
                style="background:#E1F5EE; border-color:#5DCAA5; color:#085041;">
                {{ session('success') }}
            </div>
        @endif

        {{-- Table Card --}}
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden"
            style="box-shadow: 0 1px 4px rgba(18,25,107,0.06);">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr style="background:#F8F9FC; border-bottom: 1.5px solid #E8EAF2;">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:#12196B;">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:#12196B;">Username</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:#12196B;">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:#12196B;">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:#12196B;">Completed Trips</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:#12196B;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drivers as $driver)
                            <tr style="border-bottom: 1px solid #F0F2F8; transition: background 0.15s;"
                                onmouseenter="this.style.background='#F8F9FC';"
                                onmouseleave="this.style.background='white';">

                                {{-- Name --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 font-semibold text-sm"
                                            style="background:#E6F1FB; color:#185FA5;">
                                            {{ strtoupper(substr($driver->first_name, 0, 1)) }}{{ strtoupper(substr($driver->last_name, 0, 1)) }}
                                        </div>
                                        <span class="text-sm font-semibold" style="color:#12196B;">
                                            {{ $driver->first_name }} {{ $driver->last_name }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Username --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $driver->username }}
                                </td>

                                {{-- Email --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $driver->email }}
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($driver->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                            style="background:#E1F5EE; color:#085041;">
                                            <span class="w-1.5 h-1.5 rounded-full inline-block" style="background:#1D9E75;"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                            style="background:#F1EFE8; color:#444441;">
                                            <span class="w-1.5 h-1.5 rounded-full inline-block" style="background:#888780;"></span>
                                            {{ ucfirst($driver->status) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Completed Trips --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-sm font-medium" style="color:#12196B;">
                                            {{ $driver->completed_trips_count ?? 0 }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.drivers.show', $driver) }}"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium"
                                            style="background:#E6F1FB; color:#185FA5; transition: background 0.15s, transform 0.15s;"
                                            onmouseenter="this.style.background='#B5D4F4'; this.style.transform='translateY(-1px)';"
                                            onmouseleave="this.style.background='#E6F1FB'; this.style.transform='translateY(0)';">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </a>

                                        <a href="{{ route('admin.drivers.edit', $driver) }}"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium"
                                            style="background:#EEEDFE; color:#534AB7; transition: background 0.15s, transform 0.15s;"
                                            onmouseenter="this.style.background='#CECBF6'; this.style.transform='translateY(-1px)';"
                                            onmouseleave="this.style.background='#EEEDFE'; this.style.transform='translateY(0)';">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>

                                        <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium"
                                                style="background:#FCEBEB; color:#A32D2D; transition: background 0.15s, transform 0.15s;"
                                                onmouseenter="this.style.background='#F7C1C1'; this.style.transform='translateY(-1px)';"
                                                onmouseleave="this.style.background='#FCEBEB'; this.style.transform='translateY(0)';"
                                                onclick="return confirm('Are you sure you want to delete this driver?')">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <p class="text-sm text-gray-400">No drivers found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($drivers->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $drivers->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection