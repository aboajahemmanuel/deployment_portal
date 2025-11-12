<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Book;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookSubmitted;

class BookSubmissionNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_notification_to_user_and_admins_when_book_is_submitted()
    {
        // Prevent notifications from being sent
        Notification::fake();

        // Create a user
        $user = User::factory()->create();

        // Create admin users
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');

        // Create a regular user (not admin)
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        // Book data
        $bookData = [
            'isbn' => '1234567890123',
            'title' => 'Test Book',
            'genre' => 'Fiction',
            'price' => 19.99,
            'book_type' => 'digital',
            'description' => 'A test book description',
        ];

        // Act as the user and submit a book
        $response = $this->actingAs($user)->post(route('user.books.store'), $bookData);

        // Assert the response
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        // Get the created book
        $book = Book::first();

        // Assert that notifications were sent
        Notification::assertSentTo(
            $user,
            BookSubmitted::class,
            function ($notification, $channels) use ($book) {
                return $notification->book->id === $book->id;
            }
        );

        Notification::assertSentTo(
            $admin1,
            BookSubmitted::class,
            function ($notification, $channels) use ($book) {
                return $notification->book->id === $book->id;
            }
        );

        Notification::assertSentTo(
            $admin2,
            BookSubmitted::class,
            function ($notification, $channels) use ($book) {
                return $notification->book->id === $book->id;
            }
        );

        // Assert that regular user didn't receive notification
        Notification::assertNotSentTo(
            $regularUser,
            BookSubmitted::class
        );
    }
}