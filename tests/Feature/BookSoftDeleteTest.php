<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $author;
    protected $admin;
    protected $book;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        if (!Role::where('name', 'author')->exists()) {
            Role::create(['name' => 'author']);
        }
        
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }

        // Create users
        $this->author = User::factory()->create();
        $this->author->assignRole('author');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create a book
        $this->book = Book::factory()->create([
            'user_id' => $this->author->id,
            'title' => 'Test Book',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function author_can_soft_delete_their_own_book()
    {
        $response = $this->actingAs($this->author)
            ->delete(route('author.books.destroy', $this->book));

        $response->assertRedirect(route('author.books.index'));
        $response->assertSessionHas('success');

        // Verify book is soft deleted
        $this->assertSoftDeleted('books', ['id' => $this->book->id]);
    }

    /** @test */
    public function author_can_restore_their_own_soft_deleted_book()
    {
        // First delete the book
        $this->actingAs($this->author)
            ->delete(route('author.books.destroy', $this->book));

        // Then restore it
        $response = $this->actingAs($this->author)
            ->post(route('author.books.restore', $this->book->id));

        $response->assertRedirect(route('author.books.index'));
        $response->assertSessionHas('success');

        // Verify book is restored
        $this->assertNotSoftDeleted('books', ['id' => $this->book->id]);
    }

    /** @test */
    public function admin_can_soft_delete_any_book()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.books.bulk-action'), [
                'action' => 'delete',
                'book_ids' => [$this->book->id]
            ]);

        $response->assertJson(['success' => true]);

        // Verify book is soft deleted
        $this->assertSoftDeleted('books', ['id' => $this->book->id]);
    }

    /** @test */
    public function admin_can_restore_soft_deleted_book()
    {
        // First delete the book
        $this->actingAs($this->admin)
            ->post(route('admin.books.bulk-action'), [
                'action' => 'delete',
                'book_ids' => [$this->book->id]
            ]);

        // Then restore it
        $response = $this->actingAs($this->admin)
            ->post(route('admin.books.bulk-action'), [
                'action' => 'restore',
                'book_ids' => [$this->book->id]
            ]);

        $response->assertJson(['success' => true]);

        // Verify book is restored
        $this->assertNotSoftDeleted('books', ['id' => $this->book->id]);
    }

    /** @test */
    public function soft_deleted_books_are_not_shown_in_default_queries()
    {
        // Delete the book
        $this->actingAs($this->author)
            ->delete(route('author.books.destroy', $this->book));

        // Check that the book is not returned in default queries
        $this->actingAs($this->author);
        $response = $this->get(route('author.books.index'));
        
        // The book should not be in the view
        $response->assertDontSee($this->book->title);
    }

    /** @test */
    public function soft_deleted_books_can_still_be_accessed_with_trashed_scope()
    {
        // Delete the book
        $this->actingAs($this->author)
            ->delete(route('author.books.destroy', $this->book));

        // Access the book with trashed scope
        $trashedBook = Book::withTrashed()->find($this->book->id);
        
        $this->assertNotNull($trashedBook);
        $this->assertNotNull($trashedBook->deleted_at);
    }
}