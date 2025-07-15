<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'workos_id',
        'role',
        'status',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'company_name',
        'tax_id',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'timezone',
        'locale',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'two_factor_recovery_codes' => 'array',
    ];

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot(['role', 'permissions', 'invited_by', 'joined_at'])
            ->withTimestamps('joined_at', null);
    }

    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function sshKeys(): HasMany
    {
        return $this->hasMany(SshKey::class);
    }

    public function floatingIps(): HasMany
    {
        return $this->hasMany(FloatingIp::class);
    }

    public function networks(): HasMany
    {
        return $this->hasMany(Network::class);
    }

    public function firewalls(): HasMany
    {
        return $this->hasMany(Firewall::class);
    }

    public function loadBalancers(): HasMany
    {
        return $this->hasMany(LoadBalancer::class);
    }

    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRule::class);
    }

    public function uptimeMonitors(): HasMany
    {
        return $this->hasMany(UptimeMonitor::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function loginActivity(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    public function resourceQuotas(): HasMany
    {
        return $this->hasMany(ResourceQuota::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canAccessResource($resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($resource->user_id === $this->id) {
            return true;
        }

        if ($resource->organization_id && $this->organizations->contains($resource->organization_id)) {
            return true;
        }

        return false;
    }

    public function getResourceQuota(string $resourceType): ?ResourceQuota
    {
        return $this->resourceQuotas()->where('resource_type', $resourceType)->first();
    }

    public function hasAvailableQuota(string $resourceType): bool
    {
        $quota = $this->getResourceQuota($resourceType);
        
        if (!$quota) {
            return true; // No quota set means unlimited
        }

        return $quota->current_usage < $quota->quota_limit;
    }
}