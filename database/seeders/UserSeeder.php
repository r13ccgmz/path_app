<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
        ['email' => 'admin@cpaf.uplb.edu.ph'],
        [
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'password' => Hash::make('12345678'),
            'role' => 'super-admin',
            'account_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]
        );
    }
}
