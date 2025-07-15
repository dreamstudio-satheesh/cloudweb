<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'name',
        'key_hash',
        'last_four',
        'permissions',
        'rate_limit',
        'expires_at',
        'last_used_at',
        'last_used_ip',
        'revoked_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public static function generateKey(): string
    {
        return 'hzn_' . Str::random(40);
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    public static function createNew(array $data, string $plainKey = null): array
    {
        $key = $plainKey ?: self::generateKey();
        
        $apiKey = self::create([
            ...$data,
            'key_hash' => self::hashKey($key),
            'last_four' => substr($key, -4),
        ]);

        return [
            'model' => $apiKey,
            'key' => $key,
        ];
    }

    public function isValid(): bool
    {
        if ($this->revoked_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions) {
            return true; // No permissions means full access
        }

        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    public function recordUsage(string $ip): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ]);
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}