<?php

namespace App\JsonApi\V1\Authors;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use Spatie\Permission\Models\Role;

class AuthorAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->tokenCan('authors:index') ?? false;
    }

    /**
     * Authorize the store controller action.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return false;
    }

    /**
     * Authorize the show controller action.
     */
    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->tokenCan('authors:show') ?? false;
    }

    /**
     * Authorize the update controller action.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return false;
    }

    /**
     * Authorize the destroy controller action.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return false;
    }

    /**
     * Authorize the show-related controller action.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->tokenCan('authors:show-'.$fieldName) ?? false;
    }

    /**
     * Authorize the show-relationship controller action.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->tokenCan('authors:show-'.$fieldName) ?? false;
    }

    /**
     * Authorize the update-relationship controller action.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        if ($fieldName !== 'roles') {
            return false;
        }

        $actor = $request->user();
        if (! $actor) {
            return false;
        }

        if ($denied = $this->preventSuperAdminSelfRemoval($request, $actor, $model)) {
            return $denied;
        }

        if ($actor->hasRole('super-admin')) {
            return true;
        }

        return $actor->tokenCan('authors:update-roles')
            && $actor->hasPermissionTo('authors:update-roles');
    }

    /**
     * Authorize the attach-relationship controller action.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    /**
     * Authorize the detach-relationship controller action.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    private function preventSuperAdminSelfRemoval(Request $request, User $actor, object $target): ?Response
    {
        if (! $target instanceof User || ! $actor->is($target) || ! $target->hasRole('super-admin')) {
            return null;
        }

        $newRoleIds = collect($request->input('data', []))
            ->pluck('id')
            ->filter()
            ->all();

        $stillHasSuperAdmin = Role::whereIn('id', $newRoleIds)
            ->where('name', 'super-admin')
            ->where('guard_name', 'api')
            ->exists();

        return $stillHasSuperAdmin
            ? null
            : Response::deny('You cannot remove the super-admin role from yourself.');
    }
}
