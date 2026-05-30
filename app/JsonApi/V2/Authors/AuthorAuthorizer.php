<?php

namespace App\JsonApi\V2\Authors;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use Spatie\Permission\Models\Role;

class AuthorAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     *
     * Delegates to AuthorPolicy via Gate::inspect() instead of raw tokenCan().
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return Gate::inspect('viewAny', $modelClass);
    }

    /**
     * Authorize the store controller action.
     *
     * Authors cannot be created via the API.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return Gate::inspect('create', $modelClass);
    }

    /**
     * Authorize the show controller action.
     *
     * Delegates to AuthorPolicy via Gate::inspect().
     */
    public function show(Request $request, object $model): bool|Response
    {
        return Gate::inspect('view', $model);
    }

    /**
     * Authorize the update controller action.
     *
     * Authors cannot be updated via the API.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return Gate::inspect('update', $model);
    }

    /**
     * Authorize the destroy controller action.
     *
     * Authors cannot be deleted via the API.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return Gate::inspect('delete', $model);
    }

    /**
     * Authorize the show-related controller action.
     *
     * The roles relationship requires the authors:show-roles scope (parity with V1).
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        if ($fieldName === 'roles') {
            return $request->user()?->tokenCan('authors:show-roles') ?? false;
        }

        return Gate::inspect('show'.ucfirst($fieldName), $model);
    }

    /**
     * Authorize the show-relationship controller action.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        if ($fieldName === 'roles') {
            return $request->user()?->tokenCan('authors:show-roles') ?? false;
        }

        return Gate::inspect('show'.ucfirst($fieldName), $model);
    }

    /**
     * Authorize the update-relationship controller action.
     *
     * Only the roles relationship is writable. super-admin bypasses the scope/permission
     * checks, but may not strip the super-admin role from themselves.
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

    /**
     * Deny when a super-admin actor would strip their own super-admin role.
     */
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
