<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Exceptions\HetznerApiException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;

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
     * Generate JWT token for internal service communication
     */
    private function generateInternalToken(User $user): string
    {
        $payload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'exp' => Carbon::now()->addHour()->timestamp,
            'iat' => Carbon::now()->timestamp,
            'iss' => 'laravel-service'
        ];

        return JWT::encode($payload, $this->internalApiKey, 'HS256');
    }

    /**
     * Make authenticated request to FastAPI
     */
    private function makeRequest(string $method, string $endpoint, User $user, array $data = []): array
    {
        $token = $this->generateInternalToken($user);
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
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
     * List all servers for a user
     */
    public function listServers(User $user): array
    {
        $cacheKey = "servers.user.{$user->id}";
        
        return Cache::remember($cacheKey, 60, function () use ($user) {
            return $this->makeRequest('GET', 'servers', $user);
        });
    }

    /**
     * Get a specific server
     */
    public function getServer(User $user, int $serverId): array
    {
        $cacheKey = "server.{$serverId}";
        
        return Cache::remember($cacheKey, 30, function () use ($user, $serverId) {
            return $this->makeRequest('GET', "servers/{$serverId}", $user);
        });
    }

    /**
     * Create a new server
     */
    public function createServer(User $user, array $serverData): array
    {
        $data = [
            'name' => $serverData['name'],
            'server_type' => $serverData['server_type'],
            'location' => $serverData['location'],
            'image' => $serverData['image'] ?? 'ubuntu-22.04',
            'ssh_keys' => $serverData['ssh_keys'] ?? [],
            'user_data' => $serverData['user_data'] ?? null,
            'labels' => $serverData['labels'] ?? [],
            'enable_backups' => $serverData['enable_backups'] ?? false,
            'enable_protection' => $serverData['enable_protection'] ?? false,
            'networks' => $serverData['networks'] ?? [],
            'volumes' => $serverData['volumes'] ?? [],
            'firewalls' => $serverData['firewalls'] ?? []
        ];

        $result = $this->makeRequest('POST', 'servers', $user, $data);
        
        // Clear cache
        Cache::forget("servers.user.{$user->id}");
        
        return $result;
    }

    /**
     * Execute power action on server
     */
    public function powerAction(User $user, int $serverId, string $action): array
    {
        $data = [
            'action' => $action,
            'force' => false
        ];

        $result = $this->makeRequest('POST', "servers/{$serverId}/power", $user, $data);
        
        // Clear cache
        Cache::forget("server.{$serverId}");
        Cache::forget("servers.user.{$user->id}");
        
        return $result;
    }

    /**
     * Delete a server
     */
    public function deleteServer(User $user, int $serverId): array
    {
        $result = $this->makeRequest('DELETE', "servers/{$serverId}", $user);
        
        // Clear cache
        Cache::forget("server.{$serverId}");
        Cache::forget("servers.user.{$user->id}");
        
        return $result;
    }

    /**
     * Get server metrics
     */
    public function getServerMetrics(User $user, int $serverId): array
    {
        return $this->makeRequest('GET', "servers/{$serverId}/metrics", $user);
    }

    /**
     * Get available server types
     */
    public function getServerTypes(): array
    {
        return Cache::remember('server.types', 3600, function () {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->internalApiKey}"
            ])->get("{$this->baseUrl}/server-types");

            return $response->json();
        });
    }

    /**
     * Get available locations
     */
    public function getLocations(): array
    {
        return Cache::remember('server.locations', 3600, function () {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->internalApiKey}"
            ])->get("{$this->baseUrl}/locations");

            return $response->json();
        });
    }

    /**
     * Get user's SSH keys
     */
    public function getSshKeys(User $user): array
    {
        return Cache::remember("ssh_keys.user.{$user->id}", 600, function () use ($user) {
            return $this->makeRequest('GET', 'ssh-keys', $user);
        });
    }

    /**
     * Check FastAPI health
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->json();
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
}

// Updated ServerController to use FastAPI service
namespace App\Http\Controllers;

use App\Services\FastApiService;
use App\Http\Requests\ServerCreateRequest;
use App\Http\Requests\PowerActionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServerController extends Controller
{
    private FastApiService $fastApiService;

    public function __construct(FastApiService $fastApiService)
    {
        $this->fastApiService = $fastApiService;
    }

    /**
     * List all servers for the authenticated user
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $response = $this->fastApiService->listServers($user);
            
            $servers = collect($response['servers']);
            
            $stats = [
                'active_servers' => $servers->where('status', 'running')->count(),
                'total_cores' => $servers->sum('cores') ?? 0,
                'total_ram' => $servers->sum('ram') ?? 0,
                'monthly_cost' => $servers->sum('monthly_cost') ?? 0,
            ];

            return view('servers.index', compact('servers', 'stats'));
            
        } catch (\Exception $e) {
            Log::error('Error listing servers', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to load servers');
        }
    }

    /**
     * Show the form for creating a new server
     */
    public function create()
    {
        try {
            $user = Auth::user();
            
            $serverTypes = $this->fastApiService->getServerTypes();
            $locations = $this->fastApiService->getLocations();
            $sshKeys = $this->fastApiService->getSshKeys($user);

            return view('servers.create', compact('serverTypes', 'locations', 'sshKeys'));
            
        } catch (\Exception $e) {
            Log::error('Error loading create server form', ['error' => $e->getMessage()]);
            return redirect()->route('servers.index')->with('error', 'Failed to load server creation form');
        }
    }

    /**
     * Store a newly created server
     */
    public function store(ServerCreateRequest $request)
    {
        try {
            $user = Auth::user();
            
            $serverData = $request->validated();
            $result = $this->fastApiService->createServer($user, $serverData);
            
            Log::info('Server created successfully', [
                'user_id' => $user->id,
                'server_name' => $serverData['name']
            ]);

            return redirect()->route('servers.index')
                ->with('success', 'Server creation initiated successfully');
                
        } catch (\Exception $e) {
            Log::error('Error creating server', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create server: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified server
     */
    public function show(int $id)
    {
        try {
            $user = Auth::user();
            $server = $this->fastApiService->getServer($user, $id);
            
            // Get real-time metrics
            $metrics = $this->fastApiService->getServerMetrics($user, $id);

            return view('servers.show', compact('server', 'metrics'));
            
        } catch (\Exception $e) {
            Log::error('Error showing server', ['server_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('servers.index')->with('error', 'Server not found');
        }
    }

    /**
     * Execute power action on server
     */
    public function power(PowerActionRequest $request, int $id)
    {
        try {
            $user = Auth::user();
            $action = $request->validated()['action'];
            
            $result = $this->fastApiService->powerAction($user, $id, $action);
            
            Log::info('Power action executed', [
                'user_id' => $user->id,
                'server_id' => $id,
                'action' => $action
            ]);

            return redirect()->route('servers.show', $id)
                ->with('success', "Server {$action} initiated successfully");
                
        } catch (\Exception $e) {
            Log::error('Error executing power action', [
                'server_id' => $id,
                'action' => $request->input('action'),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Failed to execute power action');
        }
    }

    /**
     * Remove the specified server
     */
    public function destroy(int $id)
    {
        try {
            $user = Auth::user();
            $this->fastApiService->deleteServer($user, $id);
            
            Log::info('Server deleted', [
                'user_id' => $user->id,
                'server_id' => $id
            ]);

            return redirect()->route('servers.index')
                ->with('success', 'Server deleted successfully');
                
        } catch (\Exception $e) {
            Log::error('Error deleting server', ['server_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete server');
        }
    }

    /**
     * Get server metrics (API endpoint)
     */
    public function metrics(int $id)
    {
        try {
            $user = Auth::user();
            $metrics = $this->fastApiService->getServerMetrics($user, $id);
            
            return response()->json($metrics);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load metrics'], 500);
        }
    }
}

// Exception class for Hetzner API errors
namespace App\Exceptions;

use Exception;

class HetznerApiException extends Exception
{
    protected $statusCode;

    public function __construct($message = "", $statusCode = 500, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

// Request validation classes
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServerCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:63|regex:/^[a-zA-Z0-9-_]+$/',
            'server_type' => 'required|string',
            'location' => 'required|string',
            'image' => 'sometimes|string',
            'ssh_keys' => 'sometimes|array',
            'ssh_keys.*' => 'string',
            'user_data' => 'sometimes|string',
            'labels' => 'sometimes|array',
            'enable_backups' => 'sometimes|boolean',
            'enable_protection' => 'sometimes|boolean',
            'networks' => 'sometimes|array',
            'volumes' => 'sometimes|array',
            'firewalls' => 'sometimes|array'
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Server name can only contain letters, numbers, hyphens, and underscores',
            'name.max' => 'Server name cannot exceed 63 characters',
        ];
    }
}

class PowerActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:start,stop,reboot,reset,shutdown'
        ];
    }
}

// Add to config/services.php
return [
    // ... other services
    
    'fastapi' => [
        'url' => env('FASTAPI_URL', 'http://fastapi:8000'),
        'internal_key' => env('INTERNAL_API_KEY'),
        'timeout' => env('FASTAPI_TIMEOUT', 30),
        'retries' => env('FASTAPI_RETRIES', 3),
    ],
];