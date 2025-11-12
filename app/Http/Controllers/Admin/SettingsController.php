<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        // Get current settings from cache or database
        $settings = [
            'site_description' => config('app.description', ''),
            'contact_email' => config('mail.from.address', ''),
            'support_email' => config('app.support_email', ''),
            'platform_commission' => config('app.platform_commission', 15),
            'min_payout_amount' => config('app.min_payout_amount', 50),
            'payout_fee' => config('app.payout_fee', 2.50),
            'currency' => config('app.currency', 'USD'),
            'auto_approve_books' => config('app.auto_approve_books', false),
            'max_file_size' => config('app.max_file_size', 50),
            'allowed_file_types' => config('app.allowed_file_types', 'pdf,epub,mobi'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_url' => 'required|url',
            'site_description' => 'nullable|string|max:1000',
            'contact_email' => 'required|email',
            'support_email' => 'required|email',
            'platform_commission' => 'required|numeric|min:0|max:100',
            'min_payout_amount' => 'required|numeric|min:1',
            'payout_fee' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,EUR,GBP',
            'auto_approve_books' => 'boolean',
            'max_file_size' => 'required|integer|min:1|max:500',
            'allowed_file_types' => 'required|string',
        ]);

        try {
            // Update environment variables
            $this->updateEnvFile([
                'APP_NAME' => '"' . $validated['site_name'] . '"',
                'APP_URL' => $validated['site_url'],
            ]);

            // Store other settings in cache/database
            $settingsToCache = [
                'site_description' => $validated['site_description'],
                'contact_email' => $validated['contact_email'],
                'support_email' => $validated['support_email'],
                'platform_commission' => $validated['platform_commission'],
                'min_payout_amount' => $validated['min_payout_amount'],
                'payout_fee' => $validated['payout_fee'],
                'currency' => $validated['currency'],
                'auto_approve_books' => $request->has('auto_approve_books'),
                'max_file_size' => $validated['max_file_size'],
                'allowed_file_types' => $validated['allowed_file_types'],
            ];

            foreach ($settingsToCache as $key => $value) {
                Cache::forever("settings.{$key}", $value);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            Mail::raw('This is a test email from your Rhymes Platform admin panel.', function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Test Email - Rhymes Platform');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateEnvFile($data)
    {
        $envFile = base_path('.env');
        $str = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $str = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $str);
        }

        file_put_contents($envFile, $str);
    }
}
