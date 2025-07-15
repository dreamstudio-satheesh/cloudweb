<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        $currentUsage = [
            'servers' => [
                ['name' => 'web-server-01', 'type' => 'CX21', 'location' => 'Falkenstein', 'price' => 5.83],
                ['name' => 'database-prod', 'type' => 'CX41', 'location' => 'Helsinki', 'price' => 16.90],
                ['name' => 'staging-server', 'type' => 'CX11', 'location' => 'Nuremberg', 'price' => 3.29],
            ],
            'volumes' => [
                ['name' => 'backup-volume', 'size' => 100, 'location' => 'Helsinki', 'price' => 4.00],
            ],
            'snapshots' => [
                ['name' => 'web-server-snapshot-2024-07', 'size' => 40, 'price' => 0.41],
            ],
            'floating_ips' => [],
            'total' => 30.43,
        ];

        $invoices = [
            [
                'id' => 'INV-2024-07',
                'date' => '2024-07-01',
                'amount' => 895.20,
                'status' => 'pending',
                'due_date' => '2024-07-15',
            ],
            [
                'id' => 'INV-2024-06',
                'date' => '2024-06-01',
                'amount' => 872.50,
                'status' => 'paid',
                'paid_date' => '2024-06-10',
            ],
            [
                'id' => 'INV-2024-05',
                'date' => '2024-05-01',
                'amount' => 798.30,
                'status' => 'paid',
                'paid_date' => '2024-05-08',
            ],
            [
                'id' => 'INV-2024-04',
                'date' => '2024-04-01',
                'amount' => 812.40,
                'status' => 'paid',
                'paid_date' => '2024-04-12',
            ],
        ];

        $paymentMethod = [
            'type' => 'credit_card',
            'brand' => 'Visa',
            'last4' => '4242',
            'expires' => '12/2025',
        ];

        $usageHistory = [
            ['month' => 'July 2024', 'amount' => 895.20, 'servers' => 15, 'status' => 'current'],
            ['month' => 'June 2024', 'amount' => 872.50, 'servers' => 14, 'status' => 'paid'],
            ['month' => 'May 2024', 'amount' => 798.30, 'servers' => 12, 'status' => 'paid'],
            ['month' => 'April 2024', 'amount' => 812.40, 'servers' => 13, 'status' => 'paid'],
            ['month' => 'March 2024', 'amount' => 756.90, 'servers' => 11, 'status' => 'paid'],
            ['month' => 'February 2024', 'amount' => 689.20, 'servers' => 10, 'status' => 'paid'],
        ];

        return view('billing.index', compact('currentUsage', 'invoices', 'paymentMethod', 'usageHistory'));
    }

    public function invoices()
    {
        $invoices = collect([
            [
                'id' => 'INV-2024-07',
                'date' => '2024-07-01',
                'amount' => 895.20,
                'status' => 'pending',
                'due_date' => '2024-07-15',
                'items' => [
                    ['description' => 'Server Usage (15 servers)', 'amount' => 845.20],
                    ['description' => 'Volume Storage (500GB)', 'amount' => 20.00],
                    ['description' => 'Snapshots (10 snapshots)', 'amount' => 10.00],
                    ['description' => 'Floating IPs (2 IPs)', 'amount' => 10.00],
                    ['description' => 'Traffic Overage', 'amount' => 10.00],
                ],
            ],
            [
                'id' => 'INV-2024-06',
                'date' => '2024-06-01',
                'amount' => 872.50,
                'status' => 'paid',
                'paid_date' => '2024-06-10',
                'items' => [
                    ['description' => 'Server Usage (14 servers)', 'amount' => 832.50],
                    ['description' => 'Volume Storage (500GB)', 'amount' => 20.00],
                    ['description' => 'Snapshots (8 snapshots)', 'amount' => 8.00],
                    ['description' => 'Floating IPs (2 IPs)', 'amount' => 10.00],
                    ['description' => 'Traffic Overage', 'amount' => 2.00],
                ],
            ],
        ])->map(function ($invoice) {
            return (object) array_merge($invoice, [
                'date' => \Carbon\Carbon::parse($invoice['date']),
                'due_date' => isset($invoice['due_date']) ? \Carbon\Carbon::parse($invoice['due_date']) : null,
                'paid_date' => isset($invoice['paid_date']) ? \Carbon\Carbon::parse($invoice['paid_date']) : null,
                'items' => collect($invoice['items'])->map(fn($item) => (object)$item),
            ]);
        });

        return view('billing.invoices', compact('invoices'));
    }
}