<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $password;

    /**
     * Create a new notification instance.
     */
    public function __construct($password)
    {
        $this->password = $password;
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
        $loginUrl = url('/login');
        
        // Safely get user roles
        $userRoles = 'User';
        try {
            if ($notifiable->roles && $notifiable->roles->count() > 0) {
                $userRoles = $notifiable->roles->pluck('name')->implode(', ');
            }
        } catch (\Exception $e) {
            // Fallback if roles relationship fails
            $userRoles = 'User';
        }
        
        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name') . ' - Your Login Credentials')
            ->greeting('Welcome to the Deployment Management System!')
            ->line('Your account has been created successfully. Here are your login credentials:')
            ->line('**Email:** ' . $notifiable->email)
            ->line('**Password:** ' . $this->password)
            ->line('**Role:** ' . $userRoles)
            ->action('Login to Your Account', $loginUrl)
            ->line('For security reasons, we recommend changing your password after your first login.')
            ->line('If you have any questions or need assistance, please contact your administrator.')
            ->salutation('Best regards, The Deployment Management Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Login credentials sent to ' . $notifiable->email,
            'user_id' => $notifiable->id,
        ];
    }
}
