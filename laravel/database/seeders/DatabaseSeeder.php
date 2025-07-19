<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Datacenter;
use App\Models\ServerType;
use App\Models\PricingPlan;
use App\Models\ResourceQuota;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@app.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create test client user
        User::create([
            'name' => 'Test Client',
            'email' => 'client@app.com',
            'email_verified_at' => now(),
            'password' => Hash::make('client123'),
            'role' => 'client',
            'status' => 'active',
        ]);

       
    }
}
