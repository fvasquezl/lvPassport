<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->tokenCan('roles:index')
            && $user->hasPermissionTo('roles:index');
    }

    public function view(?User $user, Role $role): bool
    {
        return $user?->tokenCan('roles:show')
            && $user->hasPermissionTo('roles:show');
    }

    public function create(User $user): bool
    {
        return $user->tokenCan('roles:store')
            && $user->hasPermissionTo('roles:store');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->tokenCan('roles:update')
            && $user->hasPermissionTo('roles:update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->tokenCan('roles:delete')
            && $user->hasPermissionTo('roles:delete');
    }
}
