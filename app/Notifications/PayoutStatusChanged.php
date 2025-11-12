<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Payout;

class PayoutStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public $payout;
    public $oldStatus;
    public $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payout $payout, $oldStatus, $newStatus)
    {
        $this->payout = $payout;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Payout Request Update - ₦' . number_format($this->payout->amount_requested, 2);
        $greeting = 'Hello ' . $notifiable->name . ',';
        
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line('Your payout request of ₦' . number_format($this->payout->amount_requested, 2) . ' has been updated.');

        switch ($this->newStatus) {
            case 'approved':
                $message->line('✅ Great news! Your payout request has been approved.')
                        ->line('The payment will be processed within 3-5 business days.')
                        ->line('You will receive the funds via your registered payment method.')
                        ->action('View Payout History', route('author.payouts.index'));
                break;
            case 'denied':
                $message->line('❌ Unfortunately, your payout request was denied.')
                        ->line('Reason: ' . ($this->payout->admin_notes ?: 'No specific reason provided.'))
                        ->line('You can submit a new payout request if you meet the requirements.')
                        ->action('Submit New Request', route('author.payouts.index'));
                break;
            case 'completed':
                $message->line('🎉 Your payout has been completed!')
                        ->line('The payment of ₦' . number_format($this->payout->amount_requested, 2) . ' has been sent.')
                        ->line('Please check your payment method for the funds.')
                        ->action('View Wallet', route('author.wallet.index'));
                break;
        }

        return $message->line('Thank you for being part of the Rhymes platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_status_changed',
            'payout_id' => $this->payout->id,
            'amount' => $this->payout->amount_requested,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => $this->getStatusMessage(),
            'action_url' => route('author.payouts.index'),
        ];
    }

    private function getStatusMessage()
    {
        $amount = '₦' . number_format($this->payout->amount_requested, 2);
        
        switch ($this->newStatus) {
            case 'approved':
                return 'Your payout request of ' . $amount . ' has been approved and will be processed soon.';
            case 'denied':
                return 'Your payout request of ' . $amount . ' was denied. You can submit a new request.';
            case 'completed':
                return 'Your payout of ' . $amount . ' has been completed and sent to your account.';
            default:
                return 'Your payout request of ' . $amount . ' status changed to ' . $this->newStatus;
        }
    }
}
