<?php

namespace App\Providers;

use App\Models\Server;
use App\Models\Organization;
use App\Models\ApiKey;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Policies\ServerPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ApiKeyPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class CloudPlatformServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        Server::class => ServerPolicy::class,
        Organization::class => OrganizationPolicy::class,
        ApiKey::class => ApiKeyPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Ticket::class => TicketPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        
        // Register global gates
        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });
        
        // Resource quota gate
        Gate::define('create-resource', function ($user, $resourceType) {
            return $user->hasAvailableQuota($resourceType);
        });
        
        // Organization gates
        Gate::define('manage-organization', function ($user, $organization) {
            return $user->id === $organization->owner_id || 
                   $user->organizations()
                       ->wherePivot('organization_id', $organization->id)
                       ->wherePivot('role', 'admin')
                       ->exists();
        });
        
        // Server action gates
        Gate::define('power-manage', function ($user, $server) {
            return $user->canAccessResource($server) && $server->canModify();
        });
        
        Gate::define('delete-server', function ($user, $server) {
            return $user->canAccessResource($server) && 
                   $server->canModify() && 
                   !$server->backup_enabled;
        });
        
        // Billing gates
        Gate::define('view-billing', function ($user) {
            return $user->isActive() && 
                   ($user->role === 'billing' || $user->role === 'admin' || $user->role === 'client');
        });
        
        // Support gates
        Gate::define('manage-tickets', function ($user) {
            return in_array($user->role, ['admin', 'support']);
        });
        
        // Register model observers
        $this->registerObservers();
        
        // Register custom validation rules
        $this->registerValidationRules();
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        // Server::observe(ServerObserver::class);
        // Organization::observe(OrganizationObserver::class);
        // Invoice::observe(InvoiceObserver::class);
    }

    /**
     * Register custom validation rules.
     */
    protected function registerValidationRules(): void
    {
        // SSH key validation
        \Validator::extend('ssh_key', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(ssh-rsa|ssh-ed25519|ecdsa-sha2-nistp256|ecdsa-sha2-nistp384|ecdsa-sha2-nistp521) [A-Za-z0-9+\/=]+ ?.*$/', $value);
        });
        
        // IPv4 CIDR validation
        \Validator::extend('ipv4_cidr', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}(\/([0-9]|[1-2][0-9]|3[0-2]))?$/', $value);
        });
        
        // Server name validation (Hetzner requirements)
        \Validator::extend('server_name', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9]$/', $value);
        });
    }
}