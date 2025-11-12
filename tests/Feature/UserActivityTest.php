<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

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
    }

    /**
     * Test that admin can access user activity page
     */
    public function test_admin_can_access_user_activity_page()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        // Acting as admin
        $response = $this->actingAs($admin)->get('/admin/users/activity');

        $response->assertStatus(200);
        $response->assertSee('User Activity');
        $response->assertSee('Track user activities and platform events');
    }

    /**
     * Test that non-admin users cannot access user activity page
     */
    public function test_non_admin_cannot_access_user_activity_page()
    {
        // Create regular user
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
        ]);
        $user->assignRole('user');

        // Acting as regular user
        $response = $this->actingAs($user)->get('/admin/users/activity');

        $response->assertStatus(403); // Forbidden
    }

    /**
     * Test that user activity page shows correct navigation link
     */
    public function test_user_activity_link_exists_in_admin_navigation()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        // Acting as admin
        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('User Activity');
        $response->assertSee('/admin/users/activity');
    }
}