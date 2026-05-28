<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->tokenCan('permissions:index')
            && $user->hasPermissionTo('permissions:index');
    }

    public function view(?User $user, Permission $permission): bool
    {
        return $user?->tokenCan('permissions:show')
            && $user->hasPermissionTo('permissions:show');
    }
}
