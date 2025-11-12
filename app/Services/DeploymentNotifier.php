<?php

namespace App\Services;

use App\Models\Deployment;
use App\Notifications\DeploymentNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class DeploymentNotifier
{
    /**
     * Send deployment notifications to the triggering user, all admins and all developers.
     */
    public function send(Deployment $deployment, string $type): void
    {
        // Base recipients: the user who triggered the deployment (may be null for scheduled)
        $usersToNotify = collect([$deployment->user])->filter();

        // Add all admins
        $admins = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        // Add all developers
        $developers = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'developer');
        })->get();

        $usersToNotify = $usersToNotify->merge($admins)->merge($developers)->unique('id');

        if ($usersToNotify->isEmpty()) {
            // Nothing to notify
            return;
        }

        NotificationFacade::send($usersToNotify, new DeploymentNotification($deployment, $type));
    }
}
