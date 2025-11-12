<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\User;
use App\Services\Contracts\NotificationServiceInterface;
use App\Notifications\DeploymentNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService implements NotificationServiceInterface
{
    /**
     * Send deployment notification.
     */
    public function sendDeploymentNotification(Deployment $deployment, string $type): void
    {
        $usersToNotify = $this->getDeploymentNotificationRecipients($deployment);

        if ($usersToNotify->isEmpty()) {
            return;
        }

        try {
            NotificationFacade::send($usersToNotify, new DeploymentNotification($deployment, $type));
            
            Log::info('Deployment notifications sent', [
                'deployment_id' => $deployment->id,
                'type' => $type,
                'recipient_count' => $usersToNotify->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send deployment notifications', [
                'deployment_id' => $deployment->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to specific users.
     */
    public function sendToUsers(array $users, string $type, array $data): void
    {
        $userCollection = collect($users)->filter();
        
        if ($userCollection->isEmpty()) {
            return;
        }

        try {
            // Create a generic notification based on type
            $notificationClass = $this->getNotificationClass($type);
            $notification = new $notificationClass($data);
            
            NotificationFacade::send($userCollection, $notification);
            
            Log::info('Custom notifications sent', [
                'type' => $type,
                'recipient_count' => $userCollection->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send custom notifications', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email notification.
     */
    public function sendEmail(User $user, string $subject, string $template, array $data = []): bool
    {
        try {
            Mail::send($template, $data, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)
                       ->subject($subject);
            });

            Log::info('Email notification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subject' => $subject,
                'template' => $template,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send Slack notification.
     */
    public function sendSlack(string $channel, string $message, array $attachments = []): bool
    {
        $webhookUrl = config('services.slack.webhook_url');
        
        if (!$webhookUrl) {
            Log::warning('Slack webhook URL not configured');
            return false;
        }

        try {
            $payload = [
                'channel' => $channel,
                'text' => $message,
                'username' => config('app.name', 'Deployment Manager'),
                'icon_emoji' => ':rocket:',
            ];

            if (!empty($attachments)) {
                $payload['attachments'] = $attachments;
            }

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Slack notification sent', [
                    'channel' => $channel,
                    'message_length' => strlen($message),
                ]);
                return true;
            } else {
                Log::error('Slack notification failed', [
                    'channel' => $channel,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
        } catch (Exception $e) {
            Log::error('Failed to send Slack notification', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get notification preferences for a user.
     */
    public function getUserNotificationPreferences(User $user): array
    {
        // Default preferences if not stored in database
        $defaultPreferences = [
            'deployment_success' => ['database', 'email'],
            'deployment_failure' => ['database', 'email', 'slack'],
            'security_alerts' => ['database', 'email'],
            'scheduled_deployment' => ['database'],
            'rollback_completed' => ['database', 'email'],
        ];

        // In a real implementation, you might store these in a user_notification_preferences table
        // For now, return defaults with any user-specific overrides
        return $user->notification_preferences ?? $defaultPreferences;
    }

    /**
     * Update notification preferences for a user.
     */
    public function updateUserNotificationPreferences(User $user, array $preferences): bool
    {
        try {
            // In a real implementation, you would save to a user_notification_preferences table
            // For now, we'll store in the user model as JSON
            $user->update(['notification_preferences' => $preferences]);

            Log::info('User notification preferences updated', [
                'user_id' => $user->id,
                'preferences' => $preferences,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to update notification preferences', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get notification history.
     */
    public function getNotificationHistory(User $user, int $limit = 50): array
    {
        try {
            $notifications = $user->notifications()
                ->latest()
                ->limit($limit)
                ->get();

            return $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            })->toArray();
        } catch (Exception $e) {
            Log::error('Failed to get notification history', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get recipients for deployment notifications.
     */
    private function getDeploymentNotificationRecipients(Deployment $deployment): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // Add the user who triggered the deployment
        if ($deployment->user) {
            $recipients->push($deployment->user);
        }

        // Add all admins
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();
        $recipients = $recipients->merge($admins);

        // Add all developers
        $developers = User::whereHas('roles', function ($query) {
            $query->where('name', 'developer');
        })->get();
        $recipients = $recipients->merge($developers);

        // Remove duplicates
        return $recipients->unique('id');
    }

    /**
     * Get notification class based on type.
     */
    private function getNotificationClass(string $type): string
    {
        $notificationMap = [
            'deployment' => DeploymentNotification::class,
            'security_alert' => \App\Notifications\SecurityAlertNotification::class,
            'system_alert' => \App\Notifications\SystemAlertNotification::class,
        ];

        return $notificationMap[$type] ?? DeploymentNotification::class;
    }

    /**
     * Send deployment status to Slack with rich formatting.
     */
    public function sendDeploymentSlackNotification(Deployment $deployment, string $type): bool
    {
        $project = $deployment->project;
        $user = $deployment->user;
        
        $color = match($type) {
            'success' => 'good',
            'failure' => 'danger',
            'warning' => 'warning',
            default => '#439FE0'
        };

        $emoji = match($type) {
            'success' => ':white_check_mark:',
            'failure' => ':x:',
            'warning' => ':warning:',
            default => ':rocket:'
        };

        $title = match($type) {
            'success' => 'Deployment Successful',
            'failure' => 'Deployment Failed',
            'warning' => 'Deployment Warning',
            default => 'Deployment Update'
        };

        $message = "{$emoji} {$title}: {$project->name}";
        
        $attachments = [
            [
                'color' => $color,
                'title' => $project->name,
                'fields' => [
                    [
                        'title' => 'Status',
                        'value' => ucfirst($deployment->status),
                        'short' => true
                    ],
                    [
                        'title' => 'Triggered by',
                        'value' => $user ? $user->name : 'System',
                        'short' => true
                    ],
                    [
                        'title' => 'Branch',
                        'value' => $project->current_branch,
                        'short' => true
                    ],
                    [
                        'title' => 'Commit',
                        'value' => $deployment->commit_hash ? substr($deployment->commit_hash, 0, 8) : 'N/A',
                        'short' => true
                    ],
                ],
                'footer' => 'Deployment Manager',
                'ts' => $deployment->created_at->timestamp
            ]
        ];

        return $this->sendSlack('#deployments', $message, $attachments);
    }
}
