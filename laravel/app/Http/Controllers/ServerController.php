<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServerController extends Controller
{
    private $dummyServers = [
        [
            'id' => 1,
            'name' => 'web-server-01',
            'status' => 'running',
            'ipv4' => '95.217.123.456',
            'ipv6' => '2a01:4f8:c2c:123::/64',
            'type' => 'CX21',
            'location' => 'fsn1',
            'location_name' => 'Falkenstein, Germany',
            'cores' => 2,
            'ram' => 4,
            'disk' => 40,
            'disk_type' => 'NVMe SSD',
            'price' => 5.83,
            'rdns' => 'web-server-01.example.com',
            'created_at' => '2024-01-15 10:30:00',
            'volumes' => [],
            'floating_ips' => []
        ],
        [
            'id' => 2,
            'name' => 'database-prod',
            'status' => 'running',
            'ipv4' => '95.217.123.457',
            'ipv6' => '2a01:4f8:c2c:124::/64',
            'type' => 'CX41',
            'location' => 'hel1',
            'location_name' => 'Helsinki, Finland',
            'cores' => 4,
            'ram' => 16,
            'disk' => 160,
            'disk_type' => 'NVMe SSD',
            'price' => 16.90,
            'rdns' => null,
            'created_at' => '2024-02-01 14:45:00',
            'volumes' => [
                ['id' => 1, 'name' => 'backup-volume', 'size' => 100]
            ],
            'floating_ips' => []
        ],
        [
            'id' => 3,
            'name' => 'staging-server',
            'status' => 'stopped',
            'ipv4' => '95.217.123.458',
            'ipv6' => null,
            'type' => 'CX11',
            'location' => 'nbg1',
            'location_name' => 'Nuremberg, Germany',
            'cores' => 1,
            'ram' => 2,
            'disk' => 20,
            'disk_type' => 'NVMe SSD',
            'price' => 3.29,
            'rdns' => null,
            'created_at' => '2024-03-10 09:15:00',
            'volumes' => [],
            'floating_ips' => []
        ]
    ];

    private $serverTypes = [
        // Shared vCPU
        ['id' => 'cx11', 'name' => 'CX11', 'category' => 'shared', 'cores' => 1, 'ram' => 2, 'disk' => 20, 'disk_type' => 'NVMe SSD', 'price' => 3.29],
        ['id' => 'cx21', 'name' => 'CX21', 'category' => 'shared', 'cores' => 2, 'ram' => 4, 'disk' => 40, 'disk_type' => 'NVMe SSD', 'price' => 5.83],
        ['id' => 'cx31', 'name' => 'CX31', 'category' => 'shared', 'cores' => 2, 'ram' => 8, 'disk' => 80, 'disk_type' => 'NVMe SSD', 'price' => 11.08],
        ['id' => 'cx41', 'name' => 'CX41', 'category' => 'shared', 'cores' => 4, 'ram' => 16, 'disk' => 160, 'disk_type' => 'NVMe SSD', 'price' => 16.90],
        ['id' => 'cx51', 'name' => 'CX51', 'category' => 'shared', 'cores' => 8, 'ram' => 32, 'disk' => 240, 'disk_type' => 'NVMe SSD', 'price' => 32.85],
        
        // Dedicated vCPU
        ['id' => 'ccx13', 'name' => 'CCX13', 'category' => 'dedicated', 'cores' => 2, 'ram' => 8, 'disk' => 80, 'disk_type' => 'NVMe SSD', 'price' => 23.20],
        ['id' => 'ccx23', 'name' => 'CCX23', 'category' => 'dedicated', 'cores' => 4, 'ram' => 16, 'disk' => 160, 'disk_type' => 'NVMe SSD', 'price' => 46.30],
        ['id' => 'ccx33', 'name' => 'CCX33', 'category' => 'dedicated', 'cores' => 8, 'ram' => 32, 'disk' => 240, 'disk_type' => 'NVMe SSD', 'price' => 92.50],
        ['id' => 'ccx43', 'name' => 'CCX43', 'category' => 'dedicated', 'cores' => 16, 'ram' => 64, 'disk' => 360, 'disk_type' => 'NVMe SSD', 'price' => 184.90],
        
        // ARM
        ['id' => 'cax11', 'name' => 'CAX11', 'category' => 'arm', 'cores' => 2, 'ram' => 4, 'disk' => 40, 'disk_type' => 'NVMe SSD', 'price' => 3.79],
        ['id' => 'cax21', 'name' => 'CAX21', 'category' => 'arm', 'cores' => 4, 'ram' => 8, 'disk' => 80, 'disk_type' => 'NVMe SSD', 'price' => 7.49],
        ['id' => 'cax31', 'name' => 'CAX31', 'category' => 'arm', 'cores' => 8, 'ram' => 16, 'disk' => 160, 'disk_type' => 'NVMe SSD', 'price' => 14.89],
        ['id' => 'cax41', 'name' => 'CAX41', 'category' => 'arm', 'cores' => 16, 'ram' => 32, 'disk' => 320, 'disk_type' => 'NVMe SSD', 'price' => 29.69],
    ];

    private $locations = [
        ['id' => 'fsn1', 'city' => 'Falkenstein', 'country' => 'Germany', 'flag' => '🇩🇪'],
        ['id' => 'nbg1', 'city' => 'Nuremberg', 'country' => 'Germany', 'flag' => '🇩🇪'],
        ['id' => 'hel1', 'city' => 'Helsinki', 'country' => 'Finland', 'flag' => '🇫🇮'],
        ['id' => 'ash', 'city' => 'Ashburn', 'country' => 'USA', 'flag' => '🇺🇸'],
        ['id' => 'hil', 'city' => 'Hillsboro', 'country' => 'USA', 'flag' => '🇺🇸'],
    ];

    public function index()
    {
        $servers = collect($this->dummyServers)->map(function ($server) {
            return (object) array_merge($server, [
                'volumes' => collect($server['volumes']),
                'floating_ips' => collect($server['floating_ips'])
            ]);
        });

        $stats = [
            'active_servers' => $servers->where('status', 'running')->count(),
            'total_cores' => $servers->sum('cores'),
            'total_ram' => $servers->sum('ram'),
            'monthly_cost' => $servers->sum('price'),
        ];

        return view('servers.index', compact('servers', 'stats'));
    }

    public function create()
    {
        $sshKeys = collect([
            ['id' => 1, 'name' => 'MacBook Pro Key', 'fingerprint' => 'b7:2a:...'],
            ['id' => 2, 'name' => 'CI/CD Deploy Key', 'fingerprint' => 'c4:5b:...'],
        ]);

        return view('servers.create', [
            'serverTypes' => $this->serverTypes,
            'locations' => $this->locations,
            'sshKeys' => $sshKeys
        ]);
    }

    public function store(Request $request)
    {
        // In production, this would call FastAPI to create server
        return redirect()->route('servers.index')
            ->with('success', 'Server creation started. It will be ready in a few moments.');
    }

    public function show($id)
    {
        $server = collect($this->dummyServers)->firstWhere('id', $id);
        
        if (!$server) {
            abort(404);
        }

        $server = (object) array_merge($server, [
            'volumes' => collect($server['volumes'])->map(fn($v) => (object)$v),
            'floating_ips' => collect($server['floating_ips'])->map(fn($v) => (object)$v)
        ]);

        $metrics = [
            'cpu' => rand(10, 80),
            'memory' => rand(20, 90),
            'disk' => rand(30, 70),
            'network_in' => rand(1, 20),
        ];

        return view('servers.show', compact('server', 'metrics'));
    }

    public function power(Request $request, $id)
    {
        $action = $request->input('action');
        
        // In production, this would call FastAPI
        return redirect()->route('servers.show', $id)
            ->with('success', "Server {$action} initiated successfully.");
    }

    public function destroy($id)
    {
        // In production, this would call FastAPI to delete server
        return redirect()->route('servers.index')
            ->with('success', 'Server deletion initiated.');
    }
}