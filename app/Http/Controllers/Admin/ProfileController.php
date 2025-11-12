<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        $user = auth()->user();
        
        // Calculate admin activity stats
        $stats = [
            'users_managed' => $this->getUsersManaged(),
            'books_reviewed' => $this->getBooksReviewed(),
            'payouts_processed' => $this->getPayoutsProcessed(),
            'hours_online' => $this->getHoursOnline(),
        ];

        return view('admin.profile.index', compact('stats'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:1000',
        ]);

        try {
            $user->update($validated);

            return redirect()->route('admin.profile.index')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update profile: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()
                ->with('error', 'Current password is incorrect!')
                ->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        try {
            $user->update([
                'password' => Hash::make($validated['password'])
            ]);

            return redirect()->route('admin.profile.index')
                ->with('success', 'Password updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update password: ' . $e->getMessage());
        }
    }

    public function updateNotifications(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'system_alerts' => 'boolean',
            'security_alerts' => 'boolean',
            'marketing_emails' => 'boolean',
        ]);

        try {
            // Convert checkbox values to boolean
            $preferences = [
                'email_notifications' => $request->has('email_notifications'),
                'system_alerts' => $request->has('system_alerts'),
                'security_alerts' => $request->has('security_alerts'),
                'marketing_emails' => $request->has('marketing_emails'),
            ];

            $user->update($preferences);

            return redirect()->route('admin.profile.index')
                ->with('success', 'Notification preferences updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update preferences: ' . $e->getMessage());
        }
    }

    public function exportData()
    {
        $user = auth()->user();

        try {
            $data = [
                'profile' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'bio' => $user->bio,
                    'created_at' => $user->created_at,
                    'last_login_at' => $user->last_login_at,
                ],
                'activity' => [
                    'users_managed' => $this->getUsersManaged(),
                    'books_reviewed' => $this->getBooksReviewed(),
                    'payouts_processed' => $this->getPayoutsProcessed(),
                    'hours_online' => $this->getHoursOnline(),
                ],
                'preferences' => [
                    'email_notifications' => $user->email_notifications ?? true,
                    'system_alerts' => $user->system_alerts ?? true,
                    'security_alerts' => $user->security_alerts ?? true,
                    'marketing_emails' => $user->marketing_emails ?? false,
                ],
                'exported_at' => now()->toISOString(),
            ];

            $filename = "admin-data-{$user->id}-" . now()->format('Y-m-d') . ".json";

            return response()->json($data)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getUsersManaged()
    {
        // In a real app, you'd track admin actions in an activity log
        // For now, return a mock value
        return rand(50, 200);
    }

    private function getBooksReviewed()
    {
        // In a real app, you'd track book review actions
        return rand(20, 100);
    }

    private function getPayoutsProcessed()
    {
        // In a real app, you'd track payout processing actions
        return rand(10, 50);
    }

    private function getHoursOnline()
    {
        // In a real app, you'd track session time
        return rand(100, 500);
    }
}
