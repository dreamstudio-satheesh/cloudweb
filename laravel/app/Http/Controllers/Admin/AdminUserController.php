<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    private $dummyUsers = [
        [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'client',
            'status' => 'active',
            'servers_count' => 5,
            'monthly_cost' => 89.50,
            'total_spent' => 1074.00,
            'created_at' => '2024-01-15 10:30:00',
            'last_login' => '2024-07-15 09:45:00',
        ],
        [
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@company.com',
            'role' => 'client',
            'status' => 'active',
            'servers_count' => 12,
            'monthly_cost' => 245.80,
            'total_spent' => 2949.60,
            'created_at' => '2024-02-20 14:20:00',
            'last_login' => '2024-07-14 16:30:00',
        ],
        [
            'id' => 3,
            'name' => 'Mike Johnson',
            'email' => 'mike@startup.io',
            'role' => 'client',
            'status' => 'active',
            'servers_count' => 3,
            'monthly_cost' => 45.20,
            'total_spent' => 452.00,
            'created_at' => '2024-03-01 09:00:00',
            'last_login' => '2024-07-13 11:20:00',
        ],
        [
            'id' => 4,
            'name' => 'Sarah Wilson',
            'email' => 'sarah@enterprise.com',
            'role' => 'admin',
            'status' => 'active',
            'servers_count' => 0,
            'monthly_cost' => 0,
            'total_spent' => 0,
            'created_at' => '2023-12-10 08:00:00',
            'last_login' => '2024-07-15 08:00:00',
        ],
        [
            'id' => 5,
            'name' => 'Tom Brown',
            'email' => 'tom@inactive.com',
            'role' => 'client',
            'status' => 'suspended',
            'servers_count' => 0,
            'monthly_cost' => 0,
            'total_spent' => 567.90,
            'created_at' => '2024-01-05 15:30:00',
            'last_login' => '2024-05-20 10:00:00',
        ],
        [
            'id' => 6,
            'name' => 'Emily Davis',
            'email' => 'emily@agency.net',
            'role' => 'client',
            'status' => 'active',
            'servers_count' => 8,
            'monthly_cost' => 156.70,
            'total_spent' => 1880.40,
            'created_at' => '2023-11-25 12:00:00',
            'last_login' => '2024-07-14 14:15:00',
        ],
    ];

    public function index(Request $request)
    {
        $users = collect($this->dummyUsers)->map(function ($user) {
            return (object) array_merge($user, [
                'created_at' => \Carbon\Carbon::parse($user['created_at']),
                'last_login' => \Carbon\Carbon::parse($user['last_login']),
            ]);
        });

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $users = $users->where('status', $request->status);
        }

        if ($request->has('role') && $request->role !== 'all') {
            $users = $users->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = strtolower($request->search);
            $users = $users->filter(function ($user) use ($search) {
                return str_contains(strtolower($user->name), $search) ||
                       str_contains(strtolower($user->email), $search);
            });
        }

        $stats = [
            'total_users' => count($this->dummyUsers),
            'active_users' => collect($this->dummyUsers)->where('status', 'active')->count(),
            'suspended_users' => collect($this->dummyUsers)->where('status', 'suspended')->count(),
            'total_revenue' => collect($this->dummyUsers)->sum('monthly_cost'),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        // In production, this would create a user
        return redirect()->route('admin.users')
            ->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $user = collect($this->dummyUsers)->firstWhere('id', $id);
        
        if (!$user) {
            abort(404);
        }

        $user = (object) $user;

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        // In production, this would update the user
        return redirect()->route('admin.users')
            ->with('success', 'User updated successfully.');
    }

    public function suspend($id)
    {
        // In production, this would suspend the user
        return redirect()->route('admin.users')
            ->with('success', 'User suspended successfully.');
    }

    public function activate($id)
    {
        // In production, this would activate the user
        return redirect()->route('admin.users')
            ->with('success', 'User activated successfully.');
    }
}