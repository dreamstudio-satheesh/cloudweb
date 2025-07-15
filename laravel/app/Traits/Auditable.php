<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static array $auditableEvents = ['created', 'updated', 'deleted'];
    protected static array $ignoredFields = ['created_at', 'updated_at', 'remember_token'];

    public static function bootAuditable(): void
    {
        foreach (static::$auditableEvents as $event) {
            static::$event(function (Model $model) use ($event) {
                $model->recordAudit($event);
            });
        }
    }

    protected function recordAudit(string $action): void
    {
        if ($this->shouldSkipAudit()) {
            return;
        }

        $changes = $this->getAuditChanges($action);
        
        if (empty($changes['old']) && empty($changes['new']) && $action === 'updated') {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'organization_id' => $this->organization_id ?? Auth::user()?->organizations->first()?->id,
            'action' => $this->getAuditAction($action),
            'resource_type' => $this->getTable(),
            'resource_id' => $this->id,
            'old_values' => $changes['old'],
            'new_values' => $changes['new'],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'request_id' => Request::header('X-Request-ID'),
        ]);
    }

    protected function getAuditChanges(string $action): array
    {
        $old = [];
        $new = [];

        switch ($action) {
            case 'created':
                $new = $this->getAuditableAttributes();
                break;
                
            case 'updated':
                foreach ($this->getDirty() as $key => $value) {
                    if (!$this->shouldAuditField($key)) {
                        continue;
                    }
                    
                    $old[$key] = $this->getOriginal($key);
                    $new[$key] = $value;
                }
                break;
                
            case 'deleted':
                $old = $this->getAuditableAttributes();
                break;
        }

        return compact('old', 'new');
    }

    protected function getAuditableAttributes(): array
    {
        $attributes = $this->attributesToArray();
        
        foreach (static::$ignoredFields as $field) {
            unset($attributes[$field]);
        }
        
        if (property_exists($this, 'auditIgnore')) {
            foreach ($this->auditIgnore as $field) {
                unset($attributes[$field]);
            }
        }
        
        return $attributes;
    }

    protected function getAuditAction(string $event): string
    {
        $class = class_basename($this);
        return strtolower($class) . '.' . $event;
    }

    protected function shouldAuditField(string $field): bool
    {
        if (in_array($field, static::$ignoredFields)) {
            return false;
        }
        
        if (property_exists($this, 'auditIgnore') && in_array($field, $this->auditIgnore)) {
            return false;
        }
        
        return true;
    }

    protected function shouldSkipAudit(): bool
    {
        if (property_exists($this, 'skipAudit') && $this->skipAudit === true) {
            return true;
        }
        
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return true;
        }
        
        return false;
    }

    public function audits()
    {
        return $this->morphMany(AuditLog::class, 'resource');
    }

    public function disableAuditing(): self
    {
        $this->skipAudit = true;
        return $this;
    }

    public function enableAuditing(): self
    {
        $this->skipAudit = false;
        return $this;
    }
}