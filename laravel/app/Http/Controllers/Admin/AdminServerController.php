<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FastApiService;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;

class AdminServerController extends Controller
{
    private FastApiService $fastApiService;

    public function __construct(FastApiService $fastApiService)
    {
        $this->fastApiService = $fastApiService;
    }

    public function index(Request $request)
    {
        $servers = Server::with(['user', 'serverType', 'datacenter'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('ipv4_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(20);

        // Sync with Hetzner API for live status
        $this->syncServerStatuses($servers);

        return view('admin.servers.index', compact('servers'));
    }

    public function syncAll()
    {
        // Use admin user for API calls
        $adminUser = auth()->user();
        
        try {
            $apiServers = $this->fastApiService->listServers($adminUser);
            
            foreach ($apiServers['servers'] as $apiServer) {
                // Match server to user by name pattern or labels
                $userId = $this->determineUserId($apiServer);
                
                Server::updateOrCreate(
                    ['hetzner_id' => $apiServer['id']],
                    [
                        'user_id' => $userId,
                        'name' => $apiServer['name'],
                        'status' => $apiServer['status'],
                        'ipv4_address' => $apiServer['public_net']['ipv4']['ip'] ?? null,
                        'ipv6_address' => $apiServer['public_net']['ipv6']['ip'] ?? null,
                        'server_type_id' => $this->getServerTypeId($apiServer['server_type']),
                        'datacenter_id' => $this->getDatacenterId($apiServer['datacenter']),
                        'labels' => $apiServer['labels'] ?? [],
                    ]
                );
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.servers.index')
                ->with('error', 'Failed to sync servers: ' . $e->getMessage());
        }

        return redirect()->route('admin.servers.index')
            ->with('success', 'Servers synced successfully');
    }

    private function syncServerStatuses($servers)
    {
        // Use admin user for API calls
        $adminUser = auth()->user();
        
        try {
            $apiServers = $this->fastApiService->listServers($adminUser);
            $apiServersById = collect($apiServers['servers'])->keyBy('id');
            
            foreach ($servers as $server) {
                if (isset($apiServersById[$server->hetzner_id])) {
                    $apiServer = $apiServersById[$server->hetzner_id];
                    $server->status = $apiServer['status'];
                    $server->save();
                }
            }
        } catch (\Exception $e) {
            // Log error but don't break the page
        }
    }

    private function determineUserId($apiServer)
    {
        // Check if server has user_id in labels
        if (isset($apiServer['labels']['user_id'])) {
            return $apiServer['labels']['user_id'];
        }
        
        // Try to match by server name pattern (e.g., "user123-webserver")
        if (preg_match('/^user(\d+)-/', $apiServer['name'], $matches)) {
            return $matches[1];
        }
        
        // Default to admin user if can't determine
        return auth()->id();
    }

    private function getServerTypeId($serverType)
    {
        return \App\Models\ServerType::firstOrCreate(
            ['name' => $serverType['name']],
            ['description' => $serverType['description'] ?? '']
        )->id;
    }

    private function getDatacenterId($datacenter)
    {
        return \App\Models\Datacenter::firstOrCreate(
            ['name' => $datacenter['name']],
            ['location' => $datacenter['location']['name'] ?? '']
        )->id;
    }

    public function destroy(Server $server)
    {
        try {
            // Use admin user for API call
            $adminUser = auth()->user();
            $this->fastApiService->deleteServer($adminUser, $server->hetzner_id);
            $server->delete();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}