@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Notifications</h2>
        <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-800">
            ← Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($notifications->isEmpty())
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 text-5xl mb-4">🔔</div>
            <p class="text-gray-600">No notifications yet</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($notifications as $notification)
                <div class="bg-white rounded-lg shadow-md overflow-hidden {{ !$notification->is_read ? 'border-l-4 border-blue-500' : '' }}">
                    <!-- Main Notification -->
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                        {{ $notification->type === 'status_change' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ str_replace('_', ' ', ucfirst($notification->type)) }}
                                    </span>
                                    @if(!$notification->is_read)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            New
                                        </span>
                                    @endif
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">{{ $notification->title }}</h3>
                            </div>
                            <span class="text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>

                        <p class="text-gray-700 mb-4">{{ $notification->message }}</p>

                        @if($notification->type === 'status_change' && isset($notification->data['old_status']))
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center mb-3">
                                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    <p class="text-sm font-semibold text-blue-900">Status Change Details</p>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs text-blue-700 mb-1">Driver</p>
                                        <p class="font-semibold text-blue-900">{{ $notification->data['driver_name'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-blue-700 mb-1">Previous Status</p>
                                        <p class="font-semibold text-blue-900">{{ ucfirst($notification->data['old_status'] ?? 'N/A') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-blue-700 mb-1">New Status</p>
                                        <p class="font-semibold text-green-600">{{ ucfirst($notification->data['new_status'] ?? 'N/A') }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            @if(isset($notification->data['conversation_id']))
                                <div class="mb-4">
                                    <a href="{{ route('admin.messages.index') }}?conversation={{ $notification->data['conversation_id'] }}" 
                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        Reply in Messages
                                    </a>
                                </div>
                            @endif
                        @endif

                        <!-- Reply Form -->
                        @if($notification->type === 'status_change')
                            <form action="{{ route('admin.notifications.reply', $notification) }}" method="POST" class="mt-4">
                                @csrf
                                <div class="flex gap-2">
                                    <input type="text" name="message" placeholder="Type your reply to the driver..."
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required minlength="5" maxlength="500">
                                    <button type="submit"
                                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                                        Send Reply
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>

                    <!-- Replies -->
                    @if($notification->replies->isNotEmpty())
                        <div class="bg-gray-50 border-t border-gray-200 p-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Replies</h4>
                            <div class="space-y-3">
                                @foreach($notification->replies as $reply)
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="font-medium text-gray-800">
                                                {{ $reply->data['admin_name'] ?? 'Admin' }}
                                            </span>
                                            <span class="text-sm text-gray-500">{{ $reply->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-gray-700">{{ $reply->message }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

<script>
// Auto-mark as read when viewing
document.addEventListener('DOMContentLoaded', function() {
    const unreadNotifications = document.querySelectorAll('[data-notification-id]');
    
    unreadNotifications.forEach(notification => {
        const notificationId = notification.dataset.notificationId;
        
        // Mark as read after 2 seconds of viewing
        setTimeout(() => {
            fetch(`/admin/notifications/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });
        }, 2000);
    });
});
</script>
@endsection
