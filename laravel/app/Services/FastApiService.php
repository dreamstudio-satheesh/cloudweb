<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\HetznerApiException;

class FastApiService
{
    private string $baseUrl;
    private string $internalApiKey;
    private int $timeout;
    private int $retries;

    public function __construct()
    {
        $this->baseUrl = config('services.fastapi.url', 'http://fastapi:8000');
        $this->internalApiKey = config('services.fastapi.internal_key');
        $this->timeout = config('services.fastapi.timeout', 30);
        $this->retries = config('services.fastapi.retries', 3);
    }

    /**
     * Make request to FastAPI
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $response = Http::withHeaders([
            'X-Internal-Key' => $this->internalApiKey,
            'Content-Type' => 'application/json'
        ])
        ->timeout($this->timeout)
        ->retry($this->retries, 1000)
        ->$method("{$this->baseUrl}/{$endpoint}", $data);

        if ($response->failed()) {
            $error = $response->json();
            Log::error('FastAPI request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'error' => $error
            ]);
            
            throw new HetznerApiException(
                $error['detail'] ?? 'API request failed',
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * List all servers
     */
    public function listServers(): array
    {
        return Cache::remember('servers.all', 60, function () {
            return $this->makeRequest('GET', 'servers');
        });
    }

    /**
     * Get a specific server
     */
    public function getServer(int $serverId): array
    {
        return Cache::remember("server.{$serverId}", 30, function () use ($serverId) {
            return $this->makeRequest('GET', "servers/{$serverId}");
        });
    }

    /**
     * Create a new server
     */
    public function createServer(array $serverData): array
    {
        $result = $this->makeRequest('POST', 'servers', $serverData);
        
        // Clear cache
        Cache::forget('servers.all');
        
        return $result;
    }

    /**
     * Execute power action on server
     */
    public function powerAction(int $serverId, string $action): array
    {
        $data = ['action' => $action];
        $result = $this->makeRequest('POST', "servers/{$serverId}/power", $data);
        
        // Clear cache
        Cache::forget("server.{$serverId}");
        Cache::forget('servers.all');
        
        return $result;
    }

    /**
     * Delete a server
     */
    public function deleteServer(int $serverId): array
    {
        $result = $this->makeRequest('DELETE', "servers/{$serverId}");
        
        // Clear cache
        Cache::forget("server.{$serverId}");
        Cache::forget('servers.all');
        
        return $result;
    }

    /**
     * Get server metrics
     */
    public function getServerMetrics(int $serverId): array
    {
        return $this->makeRequest('GET', "servers/{$serverId}/metrics");
    }

    /**
     * Get available server types
     */
    public function getServerTypes(): array
    {
        return Cache::remember('server.types', 3600, function () {
            return $this->makeRequest('GET', 'server-types');
        });
    }

    /**
     * Get available locations
     */
    public function getLocations(): array
    {
        return Cache::remember('server.locations', 3600, function () {
            return $this->makeRequest('GET', 'locations');
        });
    }

    /**
     * Get SSH keys
     */
    public function getSshKeys(): array
    {
        return Cache::remember('ssh_keys.all', 600, function () {
            return $this->makeRequest('GET', 'ssh-keys');
        });
    }

    /**
     * Check FastAPI health
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Internal-Key' => $this->internalApiKey])
                ->get("{$this->baseUrl}/health");
            return $response->json();
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
}