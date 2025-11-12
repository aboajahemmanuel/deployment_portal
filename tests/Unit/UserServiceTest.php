<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UserService;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }
        
        if (!Role::where('name', 'author')->exists()) {
            Role::create(['name' => 'author']);
        }
        
        if (!Role::where('name', 'user')->exists()) {
            Role::create(['name' => 'user']);
        }

        $this->userService = new UserService();
    }

    public function test_can_update_user()
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '1234567890',
            'website' => 'https://example.com',
            'bio' => 'This is a bio',
            'email_verified' => true
        ];

        $updatedUser = $this->userService->updateUser($user, $data);

        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals('updated@example.com', $updatedUser->email);
        $this->assertEquals('1234567890', $updatedUser->phone);
        $this->assertEquals('https://example.com', $updatedUser->website);
        $this->assertEquals('This is a bio', $updatedUser->bio);
        $this->assertNotNull($updatedUser->email_verified_at);
    }

    public function test_can_create_user()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'author',
            'phone' => '1234567890',
            'website' => 'https://example.com',
            'bio' => 'This is a bio'
        ];

        $user = $this->userService->createUser($data);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'website' => 'https://example.com',
            'bio' => 'This is a bio'
        ]);

        $this->assertTrue($user->hasRole('author'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_can_reset_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);

        $this->userService->resetPassword($user, 'newpassword');

        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_can_promote_to_author()
    {
        $user = User::factory()->create();

        $promotedUser = $this->userService->promoteToAuthor($user);

        $this->assertTrue($promotedUser->hasRole('author'));
        $this->assertNotNull($promotedUser->promoted_to_author_at);
    }

    public function test_get_all_roles()
    {
        $roles = $this->userService->getAllRoles();

        $this->assertCount(3, $roles);
        $this->assertEquals(['admin', 'author', 'user'], $roles->pluck('name')->sort()->values()->toArray());
    }
}