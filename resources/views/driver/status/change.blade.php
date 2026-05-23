@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Change Your Status</h2>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('driver.status.update') }}" method="POST">
            @csrf

            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">Current Status</label>
                <div class="px-4 py-3 bg-gray-100 rounded">
                    <span id="current-status-badge" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ auth()->user()->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst(auth()->user()->status) }}
                    </span>
                </div>
            </div>

            <div class="mb-6">
                <label for="status" class="block text-gray-700 font-semibold mb-2">
                    New Status <span class="text-red-500">*</span>
                </label>
                <select name="status" id="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select Status</option>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="mb-6">
                <label for="reason" class="block text-gray-700 font-semibold mb-2">
                    Reason for Status Change <span class="text-red-500">*</span>
                </label>
                <textarea name="reason" id="reason" rows="5" required
                    placeholder="Please provide a detailed reason for changing your status (minimum 10 characters)"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('reason') }}</textarea>
                <p class="text-sm text-gray-500 mt-1">Minimum 10 characters, maximum 500 characters</p>
            </div>

            <div class="flex gap-4">
                <button type="submit"
                    class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
                    Submit Status Change
                </button>
                <a href="{{ route('driver.dashboard') }}"
                    class="flex-1 bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition font-semibold text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
        <h3 class="font-semibold text-blue-900 mb-2">📋 Important Information</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• Your status change will be immediately updated in the system</li>
            <li>• All administrators will be notified of your status change</li>
            <li>• Please provide a clear and detailed reason for the change</li>
            <li>• You can change your status back at any time</li>
        </ul>
    </div>
</div>
@endsection
