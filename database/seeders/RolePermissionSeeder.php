<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view books',
            'create books',
            'edit books',
            'delete books',
            'review books',
            'manage authors',
            'view payouts',
            'approve payouts',
            'view wallet',
            'request payout',
            'view admin dashboard',
            'manage sync logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo(['view books', 'create books']);

        $authorRole = Role::firstOrCreate(['name' => 'author']);
        $authorRole->givePermissionTo([
            'view books',
            'create books', 
            'edit books',
            'view wallet',
            'request payout',
            'view payouts'
        ]);

        $developerRole = Role::firstOrCreate(['name' => 'developer']);
        // Developers can deploy projects
        $developerRole->givePermissionTo([
            'view admin dashboard'
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
    }
}