<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\ServerType;
use App\Models\Datacenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportHetznerServers extends Command
{
    protected $signature = 'hetzner:import-servers {file : The JSON file path}';
    protected $description = 'Import servers from Hetzner API JSON response';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (!$data || !isset($data['data'])) {
            $this->error("Invalid JSON format");
            return 1;
        }

        $this->info("Starting import of " . count($data['data']) . " servers...");

        DB::beginTransaction();
        
        try {
            foreach ($data['data'] as $serverData) {
                $this->processServer($serverData);
            }
            
            DB::commit();
            $this->info("Import completed successfully!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            Log::error("Hetzner import error", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }

        return 0;
    }

    private function processServer($serverData)
    {
        // Process datacenter
        $datacenter = $this->processDatacenter($serverData['datacenter']);
        
        // Process server type
        $serverType = $this->processServerType($serverData['server_type']);
        
        // Calculate traffic in GB
        $ingoingTrafficGB = isset($serverData['ingoing_traffic']) ? $serverData['ingoing_traffic'] / 1000000000 : 0;
        $outgoingTrafficGB = isset($serverData['outgoing_traffic']) ? $serverData['outgoing_traffic'] / 1000000000 : 0;
        $totalTrafficGB = $ingoingTrafficGB + $outgoingTrafficGB;
        
        // Create or update server
        $server = Server::updateOrCreate(
            ['hetzner_id' => $serverData['id']],
            [
                'name' => $serverData['name'],
                'status' => $this->mapStatus($serverData['status']),
                'server_type_id' => $serverType->id,
                'datacenter_id' => $datacenter->id,
                'ipv4_address' => $serverData['public_net']['ipv4']['ip'] ?? null,
                'ipv6_address' => isset($serverData['public_net']['ipv6']['ip']) 
                    ? explode('/', $serverData['public_net']['ipv6']['ip'])[0] 
                    : null,
                'ipv6_network' => $serverData['public_net']['ipv6']['ip'] ?? null,
                'rescue_enabled' => $serverData['rescue_enabled'] ?? false,
                'locked' => $serverData['locked'] ?? false,
                'backup_enabled' => !empty($serverData['backup_window']),
                'bandwidth_used_gb' => $totalTrafficGB,
                'labels' => $serverData['labels'] ?? [],
                'user_id' => 1, // You'll need to adjust this based on your logic
                'created_at' => $serverData['created'] ?? now(),
            ]
        );
        
        $this->info("Processed server: {$server->name} (ID: {$server->hetzner_id})");
    }

    private function processDatacenter($datacenterData)
    {
        // Extract datacenter code (e.g., "nbg1" from "nbg1-dc3")
        $code = explode('-', $datacenterData['name'])[0];
        
        return Datacenter::firstOrCreate(
            ['code' => $code],
            [
                'name' => $datacenterData['description'],
                'city' => $datacenterData['location']['city'],
                'country' => $datacenterData['location']['country'],
                'continent' => $this->getContinent($datacenterData['location']['country']),
                'latitude' => $datacenterData['location']['latitude'],
                'longitude' => $datacenterData['location']['longitude'],
            ]
        );
    }

    private function processServerType($serverTypeData)
    {
        // Get price for the first available location
        $price = $serverTypeData['prices'][0] ?? null;
        
        return ServerType::firstOrCreate(
            ['hetzner_id' => $serverTypeData['id']],
            [
                'name' => $serverTypeData['name'],
                'description' => $serverTypeData['description'],
                'architecture' => $serverTypeData['architecture'] ?? 'x86',
                'cores' => $serverTypeData['cores'],
                'cpu_type' => $serverTypeData['cpu_type'],
                'memory' => $serverTypeData['memory'],
                'disk' => $serverTypeData['disk'],
                'storage_type' => $serverTypeData['storage_type'] ?? 'local',
                'price_hourly' => $price ? $price['price_hourly']['net'] : 0,
                'price_monthly' => $price ? $price['price_monthly']['net'] : 0,
                'included_traffic' => $price ? $price['included_traffic'] : null,
                'price_per_tb_traffic' => $price ? $price['price_per_tb_traffic']['net'] : null,
                'deprecated' => $serverTypeData['deprecated'] ?? false,
                'deprecation_date' => $serverTypeData['deprecation'] ?? null,
            ]
        );
    }

    private function mapStatus($hetznerStatus)
    {
        $statusMap = [
            'running' => 'running',
            'stopped' => 'stopped',
            'off' => 'stopped',
            'paused' => 'paused',
            'rebuilding' => 'rebuilding',
            'migrating' => 'migrating',
            'starting' => 'provisioning',
            'stopping' => 'stopped',
            'deleting' => 'deleting',
            'initializing' => 'provisioning',
            'unknown' => 'error',
        ];

        return $statusMap[strtolower($hetznerStatus)] ?? 'error';
    }

    private function getContinent($countryCode)
    {
        $continentMap = [
            'DE' => 'Europe',
            'FI' => 'Europe',
            'US' => 'North America',
            // Add more mappings as needed
        ];

        return $continentMap[$countryCode] ?? 'Unknown';
    }
}