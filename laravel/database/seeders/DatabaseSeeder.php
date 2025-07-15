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

        // Seed datacenters
        $datacenters = [
            ['code' => 'fsn1', 'name' => 'Falkenstein DC Park 1', 'city' => 'Falkenstein', 'country' => 'DE', 'continent' => 'Europe', 'latitude' => 50.47612, 'longitude' => 12.37071],
            ['code' => 'nbg1', 'name' => 'Nuremberg DC Park 1', 'city' => 'Nuremberg', 'country' => 'DE', 'continent' => 'Europe', 'latitude' => 49.452102, 'longitude' => 11.076665],
            ['code' => 'hel1', 'name' => 'Helsinki DC Park 1', 'city' => 'Helsinki', 'country' => 'FI', 'continent' => 'Europe', 'latitude' => 60.169855, 'longitude' => 24.938379],
            ['code' => 'ash', 'name' => 'Ashburn, VA', 'city' => 'Ashburn', 'country' => 'US', 'continent' => 'North America', 'latitude' => 39.043757, 'longitude' => -77.487442],
            ['code' => 'hil', 'name' => 'Hillsboro, OR', 'city' => 'Hillsboro', 'country' => 'US', 'continent' => 'North America', 'latitude' => 45.542095, 'longitude' => -122.949825],
        ];

        foreach ($datacenters as $dc) {
            Datacenter::create($dc);
        }

        // Seed server types
        $serverTypes = [
            [
                'code' => 'cpx11',
                'name' => 'CPX11',
                'category' => 'standard',
                'cpu_type' => 'AMD EPYC',
                'cpu_cores' => 2,
                'memory_gb' => 2,
                'disk_gb' => 40,
                'disk_type' => 'nvme',
                'network_speed_gbps' => 20,
                'price_hourly' => 0.0076,
                'price_monthly' => 4.85,
            ],
            [
                'code' => 'cpx21',
                'name' => 'CPX21',
                'category' => 'standard',
                'cpu_type' => 'AMD EPYC',
                'cpu_cores' => 3,
                'memory_gb' => 4,
                'disk_gb' => 80,
                'disk_type' => 'nvme',
                'network_speed_gbps' => 20,
                'price_hourly' => 0.0143,
                'price_monthly' => 9.10,
            ],
            [
                'code' => 'cpx31',
                'name' => 'CPX31',
                'category' => 'standard',
                'cpu_type' => 'AMD EPYC',
                'cpu_cores' => 4,
                'memory_gb' => 8,
                'disk_gb' => 160,
                'disk_type' => 'nvme',
                'network_speed_gbps' => 20,
                'price_hourly' => 0.0280,
                'price_monthly' => 17.85,
            ],
            [
                'code' => 'cpx41',
                'name' => 'CPX41',
                'category' => 'standard',
                'cpu_type' => 'AMD EPYC',
                'cpu_cores' => 8,
                'memory_gb' => 16,
                'disk_gb' => 240,
                'disk_type' => 'nvme',
                'network_speed_gbps' => 20,
                'price_hourly' => 0.0524,
                'price_monthly' => 33.35,
            ],
            [
                'code' => 'ccx13',
                'name' => 'CCX13',
                'category' => 'dedicated',
                'cpu_type' => 'AMD EPYC 7003',
                'cpu_cores' => 2,
                'memory_gb' => 8,
                'disk_gb' => 80,
                'disk_type' => 'nvme',
                'network_speed_gbps' => 20,
                'price_hourly' => 0.0584,
                'price_monthly' => 37.15,
            ],
        ];

        foreach ($serverTypes as $type) {
            ServerType::create($type);
        }


        // Add to DatabaseSeeder
        $users = User::all();
        foreach ($users as $user) {
            ResourceQuota::create([
                'user_id' => $user->id,
                'resource_type' => 'server',
                'quota_limit' => 10,
                'current_usage' => 0,
            ]);

            ResourceQuota::create([
                'user_id' => $user->id,
                'resource_type' => 'volume',
                'quota_limit' => 50,
                'current_usage' => 0,
            ]);
        }

        // Add to DatabaseSeeder
        $pricingPlans = [
            [
                'name' => 'Server Bandwidth',
                'code' => 'bandwidth_overage',
                'resource_type' => 'bandwidth',
                'price_per_gb' => 0.01,
                'included_traffic_tb' => 20,
                'overage_price_per_tb' => 10.00,
            ],
            [
                'name' => 'Volume Storage',
                'code' => 'volume_storage',
                'resource_type' => 'volume',
                'price_per_gb' => 0.10,
                'price_monthly' => 0.10,
            ],
            [
                'name' => 'Floating IP',
                'code' => 'floating_ip',
                'resource_type' => 'floating_ip',
                'price_monthly' => 5.00,
            ],
        ];

        foreach ($pricingPlans as $plan) {
            PricingPlan::create($plan);
        }

        // Seed system settings
        $settings = [
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable maintenance mode'],
            ['key' => 'signup_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow new user registrations'],
            ['key' => 'default_server_limit', 'value' => '10', 'type' => 'integer', 'description' => 'Default server limit per user'],
            ['key' => 'billing_cycle_day', 'value' => '1', 'type' => 'integer', 'description' => 'Day of month for billing cycle'],
            ['key' => 'support_email', 'value' => 'support@app.com', 'type' => 'string', 'description' => 'Support email address'],
            ['key' => 'platform_name', 'value' => 'Cloud Hosting Platform', 'type' => 'string', 'description' => 'Platform display name'],
            ['key' => 'api_rate_limit', 'value' => '1000', 'type' => 'integer', 'description' => 'API rate limit per hour'],
            ['key' => 'backup_retention_days', 'value' => '7', 'type' => 'integer', 'description' => 'Backup retention period in days'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}
