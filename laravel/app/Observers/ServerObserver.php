<?php

namespace App\Observers;

use App\Jobs\SyncServerWithHetzner;
use App\Jobs\UpdateResourceQuota;
use App\Jobs\NotifyServerStatusChange;
use App\Models\Server;
use App\Models\UsageRecord;
use Illuminate\Support\Facades\Cache;

class ServerObserver
{
    /**
     * Handle the Server "creating" event.
     */
    public function creating(Server $server): void
    {
        // Set default labels if not provided
        if (empty($server->labels)) {
            $server->labels = [
                'created_by' => auth()->user()->email,
                'platform' => 'cloud-hosting',
            ];
        }
        
        // Generate hostname if not provided
        if (empty($server->hostname)) {
            $server->hostname = $server->name . '.cloud.local';
        }
    }

    /**
     * Handle the Server "created" event.
     */
    public function created(Server $server): void
    {
        // Update user's resource quota
        UpdateResourceQuota::dispatch($server->user, 'server', 1);
        
        // Clear cached server lists
        $this->clearServerCaches($server);
        
        // Schedule initial sync with Hetzner
        SyncServerWithHetzner::dispatch($server)->delay(now()->addSeconds(10));
    }

    /**
     * Handle the Server "updating" event.
     */
    public function updating(Server $server): bool
    {
        // Prevent changes to locked servers
        if ($server->getOriginal('locked') && !auth()->user()->isAdmin()) {
            return false;
        }
        
        // Track status changes
        if ($server->isDirty('status')) {
            $server->labels = array_merge($server->labels ?? [], [
                'last_status_change' => now()->toIso8601String(),
                'previous_status' => $server->getOriginal('status'),
            ]);
        }
        
        return true;
    }

    /**
     * Handle the Server "updated" event.
     */
    public function updated(Server $server): void
    {
        // Clear caches
        $this->clearServerCaches($server);
        
        // Handle status changes
        if ($server->wasChanged('status')) {
            NotifyServerStatusChange::dispatch(
                $server,
                $server->getOriginal('status'),
                $server->status
            );
            
            // Start tracking usage for running servers
            if ($server->status === 'running' && $server->getOriginal('status') !== 'running') {
                $this->startUsageTracking($server);
            }
            
            // Stop tracking usage for stopped servers
            if ($server->status !== 'running' && $server->getOriginal('status') === 'running') {
                $this->stopUsageTracking($server);
            }
        }
        
        // Handle backup status changes
        if ($server->wasChanged('backup_enabled')) {
            if ($server->backup_enabled) {
                // Schedule daily backups
                \App\Jobs\CreateServerBackup::dispatch($server)
                    ->delay(now()->addDay()->startOfDay());
            }
        }
    }

    /**
     * Handle the Server "deleting" event.
     */
    public function deleting(Server $server): bool
    {
        // Prevent deletion of servers with active backups
        if ($server->backups()->where('status', 'creating')->exists()) {
            return false;
        }
        
        // Prevent deletion of servers with attached volumes
        if ($server->volumes()->exists()) {
            return false;
        }
        
        return true;
    }

    /**
     * Handle the Server "deleted" event.
     */
    public function deleted(Server $server): void
    {
        // Update resource quota
        UpdateResourceQuota::dispatch($server->user, 'server', -1);
        
        // Stop usage tracking
        $this->stopUsageTracking($server);
        
        // Clear caches
        $this->clearServerCaches($server);
        
        // Clean up related resources
        $server->sshKeys()->detach();
        $server->networks()->detach();
        $server->firewalls()->detach();
    }

    /**
     * Handle the Server "restored" event.
     */
    public function restored(Server $server): void
    {
        // Update resource quota
        UpdateResourceQuota::dispatch($server->user, 'server', 1);
        
        // Clear caches
        $this->clearServerCaches($server);
        
        // Resync with Hetzner
        SyncServerWithHetzner::dispatch($server);
    }

    /**
     * Handle the Server "force deleted" event.
     */
    public function forceDeleted(Server $server): void
    {
        // Permanently delete all related data
        $server->metrics()->forceDelete();
        $server->backups()->forceDelete();
        $server->auditLogs()->delete();
        $server->usageRecords()->delete();
    }

    /**
     * Clear server-related caches.
     */
    protected function clearServerCaches(Server $server): void
    {
        Cache::tags(['servers', "user:{$server->user_id}"])->flush();
        
        if ($server->organization_id) {
            Cache::tags(["organization:{$server->organization_id}"])->flush();
        }
        
        Cache::forget("server:{$server->id}");
        Cache::forget("server:hetzner:{$server->hetzner_id}");
    }

    /**
     * Start usage tracking for a server.
     */
    protected function startUsageTracking(Server $server): void
    {
        UsageRecord::create([
            'user_id' => $server->user_id,
            'organization_id' => $server->organization_id,
            'resource_type' => 'server',
            'resource_id' => $server->id,
            'metric_type' => 'compute_hours',
            'quantity' => 0,
            'unit_price' => $server->serverType->price_hourly,
            'total_cost' => 0,
            'recorded_at' => now(),
            'metadata' => [
                'server_type' => $server->serverType->code,
                'datacenter' => $server->datacenter->code,
                'started_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Stop usage tracking for a server.
     */
    protected function stopUsageTracking(Server $server): void
    {
        $activeUsage = UsageRecord::where('resource_type', 'server')
            ->where('resource_id', $server->id)
            ->where('billed', false)
            ->whereJsonContains('metadata->started_at', '!=', null)
            ->whereJsonMissing('metadata->stopped_at')
            ->first();
            
        if ($activeUsage) {
            $startedAt = \Carbon\Carbon::parse($activeUsage->metadata['started_at']);
            $hours = $startedAt->diffInHours(now());
            
            $activeUsage->update([
                'quantity' => $hours,
                'total_cost' => $hours * $server->serverType->price_hourly,
                'metadata' => array_merge($activeUsage->metadata, [
                    'stopped_at' => now()->toIso8601String(),
                ]),
            ]);
        }
    }
}