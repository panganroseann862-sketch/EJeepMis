@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Messages</h1>
            <a href="{{ route('driver.dashboard') }}" class="text-blue-600 hover:text-blue-800">
                ← Back to Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Conversations List -->
            <div class="lg:col-span-1 bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Conversations</h3>
                </div>
                
                <!-- New Conversation Button -->
                <div class="p-4 border-b border-gray-200">
                    <button onclick="showNewConversationModal()" 
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                        + New Message
                    </button>
                </div>
                
                <div class="overflow-y-auto" style="max-height: 600px;">
                    @forelse($conversations as $conversationId => $messages)
                        @php
                            $lastMessage = $messages->first();
                            $otherUser = $lastMessage->sender_id === Auth::id() ? $lastMessage->user : $lastMessage->sender;
                            $unreadCount = $messages->where('user_id', Auth::id())->where('is_read', false)->count();
                            $isStatusChange = isset($lastMessage->data['status_change']) && $lastMessage->data['status_change'];
                        @endphp
                        <div class="conversation-item p-4 border-b border-gray-200 hover:bg-gray-50 cursor-pointer {{ $unreadCount > 0 ? 'bg-blue-50' : '' }}"
                             onclick="loadConversation('{{ $conversationId }}', {{ $otherUser->id }}, '{{ $otherUser->first_name }} {{ $otherUser->last_name }}')">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h4 class="font-semibold text-gray-900">{{ $otherUser->first_name }} {{ $otherUser->last_name }}</h4>
                                        @if($unreadCount > 0)
                                            <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">{{ $unreadCount }}</span>
                                        @endif
                                        @if($isStatusChange)
                                            <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">Status Change</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 truncate mt-1">{{ Str::limit($lastMessage->message, 50) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $lastMessage->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            <p>No conversations yet</p>
                            <p class="text-sm mt-2">Start a new conversation with an admin</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Chat Area -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow flex flex-col" style="height: 700px;">
                <div id="chat-header" class="p-4 border-b border-gray-200 hidden">
                    <h3 id="chat-recipient-name" class="font-semibold text-gray-800"></h3>
                </div>
                
                <div id="chat-messages" class="flex-1 p-4 overflow-y-auto">
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <p class="mt-2">Select a conversation to start messaging</p>
                        </div>
                    </div>
                </div>
                
                <div id="chat-input-area" class="p-4 border-t border-gray-200 hidden">
                    <form id="message-form" onsubmit="sendMessage(event)">
                        @csrf
                        <input type="hidden" id="conversation-id" name="conversation_id">
                        <input type="hidden" id="admin-id" name="admin_id">
                        <div class="flex gap-2">
                            <input type="text" id="message-input" name="message" 
                                placeholder="Type your message..." 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required maxlength="1000">
                            <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                                Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Conversation Modal -->
<div id="new-conversation-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">New Message</h3>
        <form action="{{ route('driver.messages.send') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Select Admin</label>
                <select name="admin_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Choose an admin...</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->first_name }} {{ $admin->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Message</label>
                <textarea name="message" rows="4" required maxlength="1000"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Type your message..."></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                    Send Message
                </button>
                <button type="button" onclick="hideNewConversationModal()" 
                    class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition font-semibold">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let currentConversationId = null;
let currentAdminId = null;
let pollingInterval = null;

function showNewConversationModal() {
    document.getElementById('new-conversation-modal').classList.remove('hidden');
    document.getElementById('new-conversation-modal').classList.add('flex');
}

function hideNewConversationModal() {
    document.getElementById('new-conversation-modal').classList.add('hidden');
    document.getElementById('new-conversation-modal').classList.remove('flex');
}

function loadConversation(conversationId, adminId, adminName) {
    currentConversationId = conversationId;
    currentAdminId = adminId;
    
    document.getElementById('chat-recipient-name').textContent = adminName;
    document.getElementById('chat-header').classList.remove('hidden');
    document.getElementById('chat-input-area').classList.remove('hidden');
    document.getElementById('conversation-id').value = conversationId;
    document.getElementById('admin-id').value = adminId;
    
    fetchMessages();
    
    // Start polling for new messages
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(fetchMessages, 3000);
}

function fetchMessages() {
    if (!currentConversationId) return;
    
    fetch(`/driver/messages/conversation/${currentConversationId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        displayMessages(data.messages);
    })
    .catch(error => console.error('Error fetching messages:', error));
}

function displayMessages(messages) {
    const chatMessages = document.getElementById('chat-messages');
    const currentUserId = {{ Auth::id() }};
    
    chatMessages.innerHTML = '';
    
    messages.forEach(message => {
        const isOwnMessage = message.sender_id === currentUserId;
        const isStatusChange = message.data && message.data.status_change;
        const messageDiv = document.createElement('div');
        messageDiv.className = `mb-4 flex ${isOwnMessage ? 'justify-end' : 'justify-start'}`;
        
        let statusBadge = '';
        if (isStatusChange) {
            const oldStatus = message.data.old_status || 'unknown';
            const newStatus = message.data.new_status || 'unknown';
            statusBadge = `
                <div class="mb-2 flex items-center text-xs">
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full font-semibold">
                        Status: ${oldStatus} → ${newStatus}
                    </span>
                </div>
            `;
        }
        
        messageDiv.innerHTML = `
            <div class="max-w-xs lg:max-w-md">
                ${statusBadge}
                <div class="${isOwnMessage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'} rounded-lg px-4 py-2">
                    <p>${escapeHtml(message.message)}</p>
                </div>
                <p class="text-xs text-gray-500 mt-1 ${isOwnMessage ? 'text-right' : 'text-left'}">
                    ${formatTime(message.created_at)}
                </p>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
    });
    
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function sendMessage(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('{{ route('driver.messages.send') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('message-input').value = '';
        fetchMessages();
    })
    .catch(error => console.error('Error sending message:', error));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>
@endpush
@endsection
