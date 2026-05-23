@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        <div>
            <svg class="mx-auto h-24 w-24 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                403 - Access Denied
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                You do not have permission to perform this action.
            </p>
            @if($exception->getMessage())
            <p class="mt-2 text-sm text-gray-500">
                {{ $exception->getMessage() }}
            </p>
            @endif
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
