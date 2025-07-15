<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'hetzner_id',
        'name',
        'hostname',
        'status',
        'server_type_id',
        'datacenter_id',
        'image_id',
        'ipv4_address',
        'ipv6_address',
        'ipv6_network',
        'root_password_hash',
        'user_data',
        'labels',
        'rescue_enabled',
        'locked',
        'backup_enabled',
        'iso_mounted',
        'cpu_usage',
        'memory_usage',
        'disk_usage',
        'bandwidth_used_gb',
    ];

    protected $casts = [
        'labels' => 'array',
        'rescue_enabled' => 'boolean',
        'locked' => 'boolean',
        'backup_enabled' => 'boolean',
        'cpu_usage' => 'float',
        'memory_usage' => 'float',
        'disk_usage' => 'float',
        'bandwidth_used_gb' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function serverType(): BelongsTo
    {
        return $this->belongsTo(ServerType::class);
    }

    public function datacenter(): BelongsTo
    {
        return $this->belongsTo(Datacenter::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    public function sshKeys(): BelongsToMany
    {
        return $this->belongsToMany(SshKey::class, 'server_ssh_keys')
            ->withTimestamps('added_at', null);
    }

    public function networks(): BelongsToMany
    {
        return $this->belongsToMany(Network::class, 'server_networks')
            ->withPivot(['private_ip', 'alias_ips', 'mac_address'])
            ->withTimestamps('attached_at', null);
    }

    public function firewalls(): BelongsToMany
    {
        return $this->belongsToMany(Firewall::class, 'server_firewalls')
            ->withTimestamps('attached_at', null);
    }

    public function floatingIp(): HasOne
    {
        return $this->hasOne(FloatingIp::class);
    }

    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class)
            ->where('resource_type', 'server')
            ->where('resource_id', $this->id);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRule::class)
            ->where('resource_type', 'server')
            ->where('resource_id', $this->id);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)
            ->where('resource_type', 'server')
            ->where('resource_id', $this->id);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class)
            ->where('resource_type', 'server')
            ->where('resource_id', $this->id);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }

    public function canModify(): bool
    {
        return !$this->locked && !in_array($this->status, ['deleting', 'deleted', 'migrating']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['deleting', 'deleted']);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->role === 'admin') {
            return $query;
        }
        
        return $query->where('user_id', $user->id)
            ->orWhereHas('organization.users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
    }
}