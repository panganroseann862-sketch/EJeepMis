@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Add New Driver</h2>
                    <a href="{{ route('admin.drivers.index') }}" class="text-gray-600 hover:text-gray-900">
                        Back to Drivers
                    </a>
                </div>

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.drivers.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username *</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('username') border-red-500 @enderror" 
                            required>
                        @error('username')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror" 
                            required>
                        @error('email')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password *</label>
                        <input type="password" name="password" id="password" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('password') border-red-500 @enderror" 
                            required>
                        @error('password')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">Confirm Password *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            required>
                    </div>

                    <div class="mb-4">
                        <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name *</label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('first_name') border-red-500 @enderror" 
                            required>
                        @error('first_name')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('last_name') border-red-500 @enderror" 
                            required>
                        @error('last_name')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Create Driver
                        </button>
                        <a href="{{ route('admin.drivers.index') }}" class="text-gray-600 hover:text-gray-900">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
