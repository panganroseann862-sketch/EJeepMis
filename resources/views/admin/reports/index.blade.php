@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Generate Reports</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Daily Report Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Daily Report</h2>
            <p class="text-gray-600 mb-4">Generate a comprehensive report for a specific date including trip statistics, route efficiency, driver performance, and capacity metrics.</p>
            
            <form action="{{ route('admin.reports.daily') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label for="daily_date" class="block text-sm font-medium text-gray-700 mb-1">Select Date</label>
                    <input 
                        type="date" 
                        id="daily_date" 
                        name="date" 
                        max="{{ date('Y-m-d') }}"
                        value="{{ old('date', date('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    @error('date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="daily_format" class="block text-sm font-medium text-gray-700 mb-1">Export Format</label>
                    <select 
                        id="daily_format" 
                        name="format" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                    </select>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
                >
                    Generate Daily Report
                </button>
            </form>
        </div>

        <!-- Weekly Report Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Weekly Report</h2>
            <p class="text-gray-600 mb-4">Generate an aggregated report for a date range including cumulative statistics, trends, and performance analysis.</p>
            
            <form action="{{ route('admin.reports.weekly') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input 
                        type="date" 
                        id="start_date" 
                        name="start_date" 
                        max="{{ date('Y-m-d') }}"
                        value="{{ old('start_date', date('Y-m-d', strtotime('-7 days'))) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    @error('start_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input 
                        type="date" 
                        id="end_date" 
                        name="end_date" 
                        max="{{ date('Y-m-d') }}"
                        value="{{ old('end_date', date('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    @error('end_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="weekly_format" class="block text-sm font-medium text-gray-700 mb-1">Export Format</label>
                    <select 
                        id="weekly_format" 
                        name="format" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                    </select>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
                >
                    Generate Weekly Report
                </button>
            </form>
        </div>
    </div>

    <!-- Report Information -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">Report Contents</h3>
        <p class="text-blue-800 mb-3">All reports include the following metrics:</p>
        <ul class="list-disc list-inside text-blue-800 space-y-1">
            <li>Trip statistics (total, completed, cancelled)</li>
            <li>Passenger counts and averages</li>
            <li>Route efficiency metrics (duration, on-time percentage)</li>
            <li>Driver performance data (adherence, passenger load)</li>
            <li>Schedule compliance rates</li>
            <li>Capacity statistics (average load, overcrowding incidents)</li>
        </ul>
    </div>
</div>
@endsection
