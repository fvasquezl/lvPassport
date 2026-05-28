<?php

namespace App\JsonApi\V1\Roles;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use Spatie\Permission\Models\Role;

class RoleAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return Gate::inspect('viewAny', $modelClass);
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return Gate::inspect('create', $modelClass);
    }

    public function show(Request $request, object $model): bool|Response
    {
        return Gate::inspect('view', $model);
    }

    public function update(Request $request, object $model): bool|Response
    {
        if ($this->isSuperAdminRole($model)) {
            return Response::deny('The super-admin role is immutable.');
        }

        return Gate::inspect('update', $model);
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        if ($this->isSuperAdminRole($model)) {
            return Response::deny('The super-admin role cannot be deleted.');
        }

        return Gate::inspect('delete', $model);
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return Gate::inspect('view', $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return Gate::inspect('view', $model);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        if ($this->isSuperAdminRole($model)) {
            return Response::deny('The super-admin role is immutable.');
        }

        return Gate::inspect('update', $model);
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    private function isSuperAdminRole(object $model): bool
    {
        return $model instanceof Role && $model->name === 'super-admin';
    }
}
