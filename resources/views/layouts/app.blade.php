<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'E-Jeep Monitoring') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans antialiased bg-gray-100">
    @auth
    <div class="flex h-screen overflow-hidden">
        <!-- Left Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-[#000066] to-[#000080] shadow-2xl flex flex-col">
            <!-- Logo/Brand -->
            <div class="p-6 border-b border-white/10 animate-fade-in">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm transition-all duration-300 hover:bg-white/30 hover:scale-110">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <span class="block text-lg font-bold text-white">UDD</span>
                        <span class="block text-xs text-white/70">E-Jeep Portal</span>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-4">
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.ejeeps.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.ejeeps.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        E-Jeeps
                    </a>
                    <a href="{{ route('admin.drivers.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.drivers.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Drivers
                    </a>
                    <a href="{{ route('admin.routes.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.routes.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        Routes
                    </a>
                    <a href="{{ route('admin.schedules.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.schedules.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Schedules
                    </a>
                    <a href="{{ route('admin.trips.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.trips.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Trips
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.reports.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Reports
                    </a>
                    
                    <div class="my-2 border-t border-white/10"></div>
                    
                    <a href="{{ route('admin.notifications.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.notifications.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }} relative">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        Notifications
                        <span id="admin-notification-badge" class="hidden ml-auto bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center animate-pulse"></span>
                    </a>
                    <a href="{{ route('admin.messages.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('admin.messages.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        Messages
                    </a>
                @elseif(Auth::user()->isDriver())
                    <a href="{{ route('driver.dashboard') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('driver.dashboard') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>
                    
                    <div class="my-2 border-t border-white/10"></div>
                    
                    <a href="{{ route('driver.notifications.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('driver.notifications.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }} relative">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        Notifications
                        <span id="driver-notification-badge" class="hidden ml-auto bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center animate-pulse"></span>
                    </a>
                    <a href="{{ route('driver.messages.index') }}" class="nav-link flex items-center px-6 py-3 text-sm font-medium transition-all duration-300 group {{ request()->routeIs('driver.messages.*') ? 'bg-white/10 text-white border-r-4 border-white' : 'text-white/70 hover:bg-white/10 hover:text-white hover:pl-8' }}">
                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        Messages
                    </a>
                @endif
            </nav>

            <!-- User Info & Logout -->
            <div class="border-t border-white/10 p-4 animate-fade-in">
                <div class="flex items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center text-white font-semibold backdrop-blur-sm transition-all duration-300 hover:bg-white/30 hover:scale-110">
                            {{ substr(Auth::user()->first_name, 0, 1) }}{{ substr(Auth::user()->last_name, 0, 1) }}
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-white">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</p>
                        <p class="text-xs text-white/60">{{ ucfirst(Auth::user()->role) }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-medium rounded-lg transition-all duration-300 backdrop-blur-sm hover:scale-[1.02] active:scale-[0.98]">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-gradient-to-r from-[#000080] to-[#0000a0] shadow-lg px-6 py-5 animate-slide-up">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-white">
                        @yield('page-title', 'Dashboard')
                    </h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-white/80">{{ now()->format('l, F j, Y') }}</span>
                        <div class="h-8 w-px bg-white/20"></div>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/30">
                            {{ Auth::user()->isAdmin() ? 'ADMIN ROLE' : 'DRIVER ROLE' }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div class="px-6 pt-4">
                @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-md shadow-sm mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex text-green-400 hover:text-green-600">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md shadow-sm mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex text-red-400 hover:text-red-600">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                @if(session('warning'))
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md shadow-sm mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex text-yellow-400 hover:text-yellow-600">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>
    @else
    <!-- Guest Layout (Login page) -->
    @yield('content')
    @endauth
    
    @stack('scripts')
    
    @auth
    <script>
        // Poll for new notifications every 5 seconds
        let lastNotificationCount = 0;
        
        function checkNotifications() {
            fetch('{{ route(Auth::user()->isAdmin() ? "admin.notifications.unread-count" : "driver.notifications.unread-count") }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('{{ Auth::user()->isAdmin() ? "admin" : "driver" }}-notification-badge');
                    
                    if (data.count > 0) {
                        badge.textContent = data.count > 9 ? '9+' : data.count;
                        badge.classList.remove('hidden');
                        
                        // If count increased, show visual feedback
                        if (data.count > lastNotificationCount) {
                            // Add extra pulse animation
                            badge.classList.add('animate-ping');
                            setTimeout(() => {
                                badge.classList.remove('animate-ping');
                            }, 1000);
                            
                            // Play notification sound (optional)
                            // new Audio('/notification.mp3').play().catch(() => {});
                        }
                    } else {
                        badge.classList.add('hidden');
                    }
                    
                    lastNotificationCount = data.count;
                })
                .catch(error => console.error('Error checking notifications:', error));
        }
        
        // Check immediately on page load
        checkNotifications();
        
        // Then check every 5 seconds
        setInterval(checkNotifications, 5000);
    </script>
    @endauth
</body>
</html>
