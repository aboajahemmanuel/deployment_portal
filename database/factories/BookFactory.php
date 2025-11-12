<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'isbn' => $this->generateISBN(),
            'title' => $this->faker->sentence(3),
            'genre' => $this->faker->word(),
            'price' => $this->faker->randomFloat(2, 5, 50),
            'book_type' => $this->faker->randomElement(['physical', 'digital', 'both']),
            'description' => $this->faker->paragraph(),
            'status' => 'pending',
        ];
    }

    /**
     * Generate a random ISBN-13
     */
    private function generateISBN(): string
    {
        $isbn = '978' . $this->faker->randomNumber(10, true);
        return substr($isbn, 0, 13);
    }
}