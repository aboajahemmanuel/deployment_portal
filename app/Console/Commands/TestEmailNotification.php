<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Project;
use App\Models\Deployment;
use App\Notifications\DeploymentNotification;
use Illuminate\Support\Facades\Notification;

class TestEmailNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-notification {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email notification system for deployments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing email notification system...');

        // Test basic email configuration
        $this->info('1. Testing basic email configuration...');
        
        try {
            $testEmail = $this->argument('email') ?? $this->ask('Enter your email address for testing');
            
            if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $this->error('Invalid email address provided.');
                return 1;
            }

            // Test basic email sending
            Mail::raw('This is a test email from your Deployment Management System.', function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Test Email - Deployment Management System');
            });

            $this->info('✓ Basic email test sent successfully');

        } catch (\Exception $e) {
            $this->error('✗ Basic email test failed: ' . $e->getMessage());
            $this->info('Please check your email configuration in .env file');
            return 1;
        }

        // Test deployment notification
        $this->info('2. Testing deployment notification...');
        
        try {
            // Find or create a test user
            $testUser = User::first();
            if (!$testUser) {
                $this->error('No users found in database. Please create a user first.');
                return 1;
            }

            // Find or create a test project
            $testProject = Project::first();
            if (!$testProject) {
                $this->error('No projects found in database. Please create a project first.');
                return 1;
            }

            // Create a mock deployment
            $testDeployment = new Deployment([
                'id' => 999999,
                'project_id' => $testProject->id,
                'user_id' => $testUser->id,
                'commit_hash' => 'test-commit-hash',
                'status' => 'success',
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            // Set the project relationship manually
            $testDeployment->setRelation('project', $testProject);
            $testDeployment->setRelation('user', $testUser);

            // Create a test user for notification
            $notificationUser = new User([
                'name' => 'Test User',
                'email' => $testEmail,
            ]);

            // Send deployment notification
            $notificationUser->notify(new DeploymentNotification($testDeployment, 'success'));

            $this->info('✓ Deployment notification sent successfully');

        } catch (\Exception $e) {
            $this->error('✗ Deployment notification test failed: ' . $e->getMessage());
            return 1;
        }

        // Test the deployment notifier service
        $this->info('3. Testing DeploymentNotifier service...');
        
        try {
            // Get all admin and developer users
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->count();

            $developers = User::whereHas('roles', function ($query) {
                $query->where('name', 'developer');
            })->count();

            $this->info("Found {$admins} admin(s) and {$developers} developer(s) who would receive notifications");

            if ($admins + $developers === 0) {
                $this->warn('No admin or developer users found. Notifications will only be sent to deployment trigger user.');
            }

        } catch (\Exception $e) {
            $this->error('✗ DeploymentNotifier service test failed: ' . $e->getMessage());
        }

        $this->info('');
        $this->info('Email notification test completed!');
        $this->info('Check your email inbox for the test messages.');
        $this->info('');
        $this->info('If you received the emails, your scheduled deployments should now send notifications.');
        $this->info('If not, please check:');
        $this->info('- Your .env email configuration');
        $this->info('- Laravel logs in storage/logs/laravel.log');
        $this->info('- Your email provider settings (Gmail app passwords, etc.)');

        return 0;
    }
}
