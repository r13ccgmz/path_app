<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles matching the user.role enum
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'observer', 'guard_name' => 'web']);

        // Assign super_admin role to the default admin user
        $admin = User::where('email', 'admin@cpaf.uplb.edu.ph')->first();
        if ($admin && !$admin->hasRole('super_admin')) {
            $admin->assignRole($superAdmin);
        }
    }
}
