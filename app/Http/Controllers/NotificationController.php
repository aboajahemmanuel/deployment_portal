<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $notifications = Notification::forUser(auth()->id())
            ->latest()
            ->paginate(20);
            
        return response()->json($notifications);
    }

    public function unread()
    {
        $notifications = Notification::forUser(auth()->id())
            ->unread()
            ->latest()
            ->limit(10)
            ->get();
            
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->count()
        ]);
    }

    public function markAllAsRead()
    {
        Notification::forUser(auth()->id())
            ->unread()
            ->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::forUser(auth()->id())->find($id);
        
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false], 404);
    }

    public function toggleDarkMode(Request $request)
    {
        $user = auth()->user();
        $darkMode = $request->input('dark_mode', false);
        
        // Store in session for now, could be saved to user preferences later
        session(['dark_mode' => $darkMode]);
        
        return response()->json(['success' => true, 'dark_mode' => $darkMode]);
    }
}
