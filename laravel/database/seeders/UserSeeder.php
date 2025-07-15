<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'John Doe',
            'email' => 'client@example.com',
            'password' => bcrypt('password'),
            'role' => 'client',
            'status' => 'active',
        ]);
    }
}
