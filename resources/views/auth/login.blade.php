@extends('layouts.app')

@section('content')
<div class="flex min-h-screen">
    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-2/5 bg-gradient-to-br from-[#000066] to-[#0000a0] text-white p-12 flex-col justify-center">
        <div class="max-w-md">
            <div class="flex items-center mb-8 animate-fade-in">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm transition-all duration-300 hover:bg-white/30 hover:scale-110">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold">UDD E-Jeep Portal</h1>
                    <p class="text-sm text-white/80">Universidad de Dagupan</p>
                </div>
            </div>
            
            <h2 class="text-3xl font-bold mb-4 animate-slide-up" style="animation-delay: 0.2s;">Your campus companion</h2>
            <p class="text-lg text-white/90 leading-relaxed animate-slide-up" style="animation-delay: 0.3s;">
                Stay on top of your transportation journey with access to routes, schedules, and real-time monitoring.
            </p>
        </div>
    </div>

    <!-- Right Panel - Login Form -->
    <div class="flex-1 flex items-center justify-center bg-gray-50 p-8">
        <div class="w-full max-w-md animate-slide-up">
            <div class="bg-white rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome back</h2>
                <p class="text-gray-500 text-sm mb-8">Sign in with your credentials to access your personalized portal.</p>
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Username -->
                    <div class="mb-5">
                        <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">
                            Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input 
                                id="username" 
                                type="text" 
                                name="username" 
                                value="{{ old('username') }}" 
                                required 
                                autofocus
                                placeholder="cddejuandelacruz"
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#000080] focus:border-transparent transition-all duration-300 @error('username') border-red-500 @enderror"
                            >
                        </div>
                        @error('username')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                required
                                placeholder="••••••••"
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#000080] focus:border-transparent transition-all duration-300 @error('password') border-red-500 @enderror"
                            >
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg id="eye-icon" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-black hover:bg-gray-800 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-300 mb-4 hover:scale-[1.02] active:scale-[0.98] hover:shadow-lg"
                    >
                        Log in
                    </button>
                    
                    <p class="text-center text-xs text-gray-500 mt-4">
                        This is a secure area of the portal. Please keep your credentials safe and avoid using shared devices.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
    } else {
        passwordInput.type = 'password';
    }
}
</script>
@endsection
