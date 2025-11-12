<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            // Create sample notifications for each user
            $notifications = [
                [
                    'id' => Str::uuid(),
                    'type' => 'App\Notifications\BookPublished',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'message' => 'Your book has been successfully published!',
                        'book_title' => 'Sample Book Title'
                    ]),
                    'title' => 'Book Published',
                    'message' => 'Your book "Sample Book Title" has been successfully published and is now available for readers.',
                    'icon' => 'ni ni-book',
                    'read_at' => null,
                    'created_at' => now()->subHours(2),
                    'updated_at' => now()->subHours(2),
                ],
                [
                    'id' => Str::uuid(),
                    'type' => 'App\Notifications\BookSold',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'message' => 'You made a sale!',
                        'amount' => '₦15.99',
                        'book_title' => 'Sample Book Title'
                    ]),
                    'title' => 'New Sale',
                    'message' => 'Congratulations! Someone purchased your book for ₦15.99.',
                    'icon' => 'ni ni-coins',
                    'read_at' => null,
                    'created_at' => now()->subHours(5),
                    'updated_at' => now()->subHours(5),
                ],
                [
                    'id' => Str::uuid(),
                    'type' => 'App\Notifications\SystemAlert',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'message' => 'Welcome to the platform!',
                        'action_url' => '/author/profile'
                    ]),
                    'title' => 'Welcome!',
                    'message' => 'Welcome to Rhymes Platform! Complete your profile to get started.',
                    'icon' => 'ni ni-user-check',
                    'read_at' => now()->subDay(),
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ],
            ];

            foreach ($notifications as $notification) {
                DB::table('notifications')->insert($notification);
            }
        }
    }
}
