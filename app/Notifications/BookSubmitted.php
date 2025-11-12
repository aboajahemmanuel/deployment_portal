<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Book;

class BookSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $book;

    /**
     * Create a new notification instance.
     */
    public function __construct(Book $book)
    {
        // Eager load the user relationship to avoid N+1 issues
        $this->book = $book->load('user');
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
     * Get the mail representation of the notification for the user.
     */
    public function toMailUser(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Book Submitted: ' . $this->book->title)
            ->view('emails.book-submitted-user', [
                'user' => $notifiable,
                'book' => $this->book,
            ]);
    }

    /**
     * Get the mail representation of the notification for admins.
     */
    public function toMailAdmin(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Book Submission: ' . $this->book->title)
            ->view('emails.book-submitted-admin', [
                'user' => $notifiable,
                'book' => $this->book,
            ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Check if the notifiable is an admin or the user who submitted the book
        if ($notifiable->hasRole('admin')) {
            return $this->toMailAdmin($notifiable);
        } else {
            return $this->toMailUser($notifiable);
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Check if the notifiable is an admin or the user who submitted the book
        if ($notifiable->hasRole('admin')) {
            return [
                'type' => 'book_submitted_admin',
                'book_id' => $this->book->id,
                'book_title' => $this->book->title,
                'author_name' => $this->book->user->name,
                'message' => 'New book submission: "' . $this->book->title . '" by ' . $this->book->user->name,
                'action_url' => url('/admin/books/' . $this->book->id),
            ];
        } else {
            return [
                'type' => 'book_submitted_user',
                'book_id' => $this->book->id,
                'book_title' => $this->book->title,
                'message' => 'Your book "' . $this->book->title . '" has been submitted for review',
                'action_url' => url('/dashboard'),
            ];
        }
    }
}