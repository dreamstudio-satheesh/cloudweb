<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_servers' => 47,
            'active_servers' => 42,
            'total_users' => 156,
            'active_users' => 143,
            'cpu_usage' => 68,
            'total_cores' => 284,
            'ram_usage' => 72,
            'total_ram' => 1024,
            'monthly_revenue' => 4567.89,
            'revenue_growth' => 12.5,
        ];

        $activities = collect([
            [
                'id' => 1,
                'type' => 'server_created',
                'description' => 'created server web-prod-03',
                'user' => ['name' => 'John Doe'],
                'created_at' => now()->subMinutes(15),
            ],
            [
                'id' => 2,
                'type' => 'server_deleted',
                'description' => 'deleted server staging-old',
                'user' => ['name' => 'Jane Smith'],
                'created_at' => now()->subHours(2),
            ],
            [
                'id' => 3,
                'type' => 'user_registered',
                'description' => 'registered a new account',
                'user' => ['name' => 'Mike Johnson'],
                'created_at' => now()->subHours(5),
            ],
            [
                'id' => 4,
                'type' => 'server_created',
                'description' => 'created server database-replica',
                'user' => ['name' => 'Sarah Wilson'],
                'created_at' => now()->subDays(1),
            ],
        ])->map(function ($activity) {
            $activity['user'] = (object) $activity['user'];
            $activity['created_at'] = \Carbon\Carbon::parse($activity['created_at']);
            return (object) $activity;
        });

        $health = [
            'api_response' => 45, // ms
            'db_load' => 35, // percentage
            'queue_jobs' => 12, // count
        ];

        $users = collect([
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'client',
                'servers_count' => 5,
                'monthly_cost' => 89.50,
                'status' => 'active',
                'created_at' => '2024-01-15',
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@company.com',
                'role' => 'client',
                'servers_count' => 12,
                'monthly_cost' => 245.80,
                'status' => 'active',
                'created_at' => '2024-02-20',
            ],
            [
                'id' => 3,
                'name' => 'Mike Johnson',
                'email' => 'mike@startup.io',
                'role' => 'client',
                'servers_count' => 3,
                'monthly_cost' => 45.20,
                'status' => 'active',
                'created_at' => '2024-03-01',
            ],
            [
                'id' => 4,
                'name' => 'Sarah Wilson',
                'email' => 'sarah@enterprise.com',
                'role' => 'admin',
                'servers_count' => 0,
                'monthly_cost' => 0,
                'status' => 'active',
                'created_at' => '2023-12-10',
            ],
            [
                'id' => 5,
                'name' => 'Tom Brown',
                'email' => 'tom@inactive.com',
                'role' => 'client',
                'servers_count' => 0,
                'monthly_cost' => 0,
                'status' => 'suspended',
                'created_at' => '2024-01-05',
            ],
        ])->map(fn($user) => (object) $user);

        return view('admin.dashboard', compact('stats', 'activities', 'health', 'users'));
    }
}