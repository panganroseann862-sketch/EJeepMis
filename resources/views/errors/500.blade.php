@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        <div>
            <svg class="mx-auto h-24 w-24 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                500 - Server Error
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Something went wrong on our end. Please try again later.
            </p>
        </div>
        <div class="mt-8">
            <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Go Back
            </a>
            @auth
                @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Dashboard
                </a>
                @elseif(Auth::user()->isDriver())
                <a href="{{ route('driver.dashboard') }}" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Dashboard
                </a>
                @endif
            @endauth
        </div>
    </div>
</div>
@endsection
