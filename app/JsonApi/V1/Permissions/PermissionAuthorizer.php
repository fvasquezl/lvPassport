<?php

namespace App\JsonApi\V1\Permissions;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class PermissionAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return Gate::inspect('viewAny', $modelClass);
    }

    public function show(Request $request, object $model): bool|Response
    {
        return Gate::inspect('view', $model);
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }
}
