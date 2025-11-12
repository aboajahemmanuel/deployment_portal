@if(auth()->check() && auth()->user()->unreadNotifications->count() > 0)
    <div class="relative" x-data="{ open: false }">
        <!-- Notification Bell -->
        <button @click="open = !open" class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-md">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                    {{ auth()->user()->unreadNotifications->count() }}
                </span>
            @endif
        </button>

        <!-- Notifications Dropdown -->
        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="py-1">
                <div class="px-4 py-2 text-sm font-semibold text-gray-900 border-b">
                    Notifications ({{ auth()->user()->unreadNotifications->count() }})
                </div>
                
                <div class="max-h-96 overflow-y-auto">
                    @forelse(auth()->user()->unreadNotifications->take(10) as $notification)
                        <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    @if($notification->data['type'] === 'book_status_changed')
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    @elseif($notification->data['type'] === 'payout_status_changed')
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm text-gray-900">{{ $notification->data['message'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            @if(isset($notification->data['action_url']))
                                <div class="mt-2">
                                    <a href="{{ $notification->data['action_url'] }}" class="text-xs text-indigo-600 hover:text-indigo-900">
                                        View Details →
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-3 text-sm text-gray-500">
                            No new notifications
                        </div>
                    @endforelse
                </div>

                @if(auth()->user()->unreadNotifications->count() > 0)
                    <div class="px-4 py-2 border-t">
                        <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900">
                                Mark all as read
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
