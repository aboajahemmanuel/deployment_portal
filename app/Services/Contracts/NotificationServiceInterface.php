<?php

namespace App\Services\Contracts;

use App\Models\Deployment;
use App\Models\User;

interface NotificationServiceInterface
{
    /**
     * Send deployment notification.
     */
    public function sendDeploymentNotification(Deployment $deployment, string $type): void;

    /**
     * Send notification to specific users.
     */
    public function sendToUsers(array $users, string $type, array $data): void;

    /**
     * Send email notification.
     */
    public function sendEmail(User $user, string $subject, string $template, array $data = []): bool;

    /**
     * Send Slack notification.
     */
    public function sendSlack(string $channel, string $message, array $attachments = []): bool;

    /**
     * Get notification preferences for a user.
     */
    public function getUserNotificationPreferences(User $user): array;

    /**
     * Update notification preferences for a user.
     */
    public function updateUserNotificationPreferences(User $user, array $preferences): bool;

    /**
     * Get notification history.
     */
    public function getNotificationHistory(User $user, int $limit = 50): array;
}
