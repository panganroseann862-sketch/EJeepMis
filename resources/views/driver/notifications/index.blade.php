@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
            <p class="text-gray-600 mt-1">Stay updated with route changes, schedule updates, and capacity alerts.</p>
        </div>

        <!-- Notifications List -->
        <div class="bg-white rounded-lg shadow">
            @if($notifications->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($notifications as $notification)
                <div class="notification-item p-6 hover:bg-gray-50 transition {{ $notification->is_read ? 'bg-white' : 'bg-blue-50' }}" 
                     data-notification-id="{{ $notification->id }}">
                    <div class="flex items-start">
                        <!-- Icon based on notification type -->
                        <div class="flex-shrink-0">
                            @if($notification->type === 'route_update')
                                <div class="bg-blue-100 rounded-full p-3">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                    </svg>
                                </div>
                            @elseif($notification->type === 'schedule_change')
                                <div class="bg-yellow-100 rounded-full p-3">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @elseif($notification->type === 'capacity_warning')
                                <div class="bg-red-100 rounded-full p-3">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                            @elseif($notification->type === 'admin_reply')
                                <div class="bg-green-100 rounded-full p-3">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="bg-gray-100 rounded-full p-3">
                                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <!-- Notification Content -->
                        <div class="ml-4 flex-1">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h3 class="text-base font-semibold text-gray-900">{{ $notification->title }}</h3>
                                        @if(!$notification->is_read)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white">
                                                New
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-gray-700">{{ $notification->message }}</p>
                                    
                                    @if($notification->type === 'admin_reply' && isset($notification->data['admin_name']))
                                        <div class="mt-3 bg-green-50 border border-green-200 rounded-lg p-3">
                                            <p class="text-xs font-semibold text-green-800 mb-1">
                                                💬 Reply from {{ $notification->data['admin_name'] }}
                                            </p>
                                        </div>
                                    @endif
                                    
                                    <div class="mt-2 flex items-center text-xs text-gray-500">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                                        <span class="mx-2">•</span>
                                        <span>{{ $notification->created_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                </div>

                                <!-- Mark as Read Button -->
                                @if(!$notification->is_read)
                                <button 
                                    onclick="markAsRead({{ $notification->id }})"
                                    class="ml-4 text-sm text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap">
                                    Mark as read
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
            @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No notifications</h3>
                <p class="mt-2 text-sm text-gray-500">You're all caught up! Check back later for updates.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch(`/driver/notifications/${notificationId}/mark-as-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the UI to reflect the notification is read
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationItem) {
                // Remove the blue background
                notificationItem.classList.remove('bg-blue-50');
                notificationItem.classList.add('bg-white');
                
                // Remove the "New" badge
                const newBadge = notificationItem.querySelector('.bg-blue-600');
                if (newBadge) {
                    newBadge.remove();
                }
                
                // Remove the "Mark as read" button
                const markAsReadBtn = notificationItem.querySelector('button');
                if (markAsReadBtn) {
                    markAsReadBtn.remove();
                }
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        alert('Failed to mark notification as read. Please try again.');
    });
}
</script>
@endsection
