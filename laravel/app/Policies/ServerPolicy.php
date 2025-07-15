<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Server $server): bool
    {
        return $user->canAccessResource($server);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasAvailableQuota('server');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && $server->canModify();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && 
               $server->canModify() && 
               !$server->locked;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Server $server): bool
    {
        return $user->canAccessResource($server);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Server $server): bool
    {
        return $user->isAdmin() && !$server->locked;
    }

    /**
     * Determine whether the user can perform power actions.
     */
    public function powerManage(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && 
               $server->canModify() &&
               in_array($server->status, ['running', 'stopped']);
    }

    /**
     * Determine whether the user can resize the server.
     */
    public function resize(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && 
               $server->canModify() &&
               $server->status === 'stopped';
    }

    /**
     * Determine whether the user can manage backups.
     */
    public function manageBackups(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && $server->canModify();
    }

    /**
     * Determine whether the user can manage networks.
     */
    public function manageNetworks(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && $server->canModify();
    }

    /**
     * Determine whether the user can manage firewalls.
     */
    public function manageFirewalls(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && $server->canModify();
    }

    /**
     * Determine whether the user can access console.
     */
    public function accessConsole(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && 
               $server->isRunning() &&
               !$server->locked;
    }

    /**
     * Determine whether the user can view metrics.
     */
    public function viewMetrics(User $user, Server $server): bool
    {
        return $user->canAccessResource($server);
    }

    /**
     * Determine whether the user can enable rescue mode.
     */
    public function enableRescue(User $user, Server $server): bool
    {
        return $user->canAccessResource($server) && 
               $server->canModify() &&
               !$server->rescue_enabled;
    }
}