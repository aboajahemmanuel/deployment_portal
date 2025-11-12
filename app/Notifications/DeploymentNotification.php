<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Deployment;

class DeploymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $deployment;
    protected $type; // 'success' or 'failure'

    /**
     * Create a new notification instance.
     */
    public function __construct(Deployment $deployment, string $type)
    {
        $this->deployment = $deployment;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->deployment->project;
        $user = $this->deployment->user;
        
        return (new MailMessage)
            ->subject('Deployment ' . ucfirst($this->type) . ': ' . $project->name)
            ->view('emails.deployment-notification', [
                'notifiable' => $notifiable,
                'deployment' => $this->deployment,
                'project' => $project,
                'user' => $user,
                'type' => $this->type,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'deployment_id' => $this->deployment->id,
            'project_name' => $this->deployment->project->name,
            'type' => $this->type,
            'completed_at' => $this->deployment->completed_at,
        ];
    }
}