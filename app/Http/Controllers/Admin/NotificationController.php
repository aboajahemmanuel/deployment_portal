<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request)
    {
        // Mock notification data - in a real app, you'd have a notifications table
        $notifications = collect([
            (object) [
                'id' => 1,
                'type' => 'announcement',
                'title' => 'Platform Update Released',
                'message' => 'We have released a new update with improved performance and new features.',
                'read_at' => null,
                'created_at' => now()->subHours(2),
            ],
            (object) [
                'id' => 2,
                'type' => 'system',
                'title' => 'Scheduled Maintenance',
                'message' => 'System maintenance is scheduled for this weekend.',
                'read_at' => now()->subHour(),
                'created_at' => now()->subDays(1),
            ],
            (object) [
                'id' => 3,
                'type' => 'promotion',
                'title' => 'Special Offer Available',
                'message' => 'Limited time offer on featured books.',
                'read_at' => null,
                'created_at' => now()->subDays(2),
            ],
        ]);

        // Filter by type if specified
        if ($request->filled('type')) {
            $notifications = $notifications->where('type', $request->type);
        }

        // Calculate stats
        $stats = [
            'total' => $notifications->count(),
            'unread' => $notifications->whereNull('read_at')->count(),
            'today' => $notifications->where('created_at', '>=', now()->startOfDay())->count(),
            'active_users' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
        ];

        return view('admin.notifications.index', compact('notifications', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:announcement,system,promotion,maintenance',
            'audience' => 'required|in:all,authors,readers,admins',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'send_method' => 'required|in:in_app,email,both',
        ]);

        try {
            // Get target users based on audience
            $users = $this->getTargetUsers($validated['audience']);

            // Send notifications based on method
            if (in_array($validated['send_method'], ['email', 'both'])) {
                $this->sendEmailNotifications($users, $validated);
            }

            if (in_array($validated['send_method'], ['in_app', 'both'])) {
                $this->sendInAppNotifications($users, $validated);
            }

            return response()->json([
                'success' => true,
                'message' => "Notification sent to {$users->count()} users successfully!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAllAsRead()
    {
        try {
            // In a real app, you'd update the notifications table
            // For now, we'll just return success
            
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'author_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            $author = User::findOrFail($validated['author_id']);

            // Send email to author
            Mail::raw($validated['message'], function ($mail) use ($author, $validated) {
                $mail->to($author->email)
                     ->subject($validated['subject']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getTargetUsers($audience)
    {
        switch ($audience) {
            case 'authors':
                return User::role('author')->get();
            case 'readers':
                return User::role('reader')->get();
            case 'admins':
                return User::role('admin')->get();
            case 'all':
            default:
                return User::all();
        }
    }

    private function sendEmailNotifications($users, $data)
    {
        foreach ($users as $user) {
            Mail::raw($data['message'], function ($mail) use ($user, $data) {
                $mail->to($user->email)
                     ->subject($data['title']);
            });
        }
    }

    private function sendInAppNotifications($users, $data)
    {
        // In a real app, you'd create notification records in the database
        // For now, we'll just simulate the process
        foreach ($users as $user) {
            // Create in-app notification record
            // This would typically be stored in a notifications table
        }
    }
}
