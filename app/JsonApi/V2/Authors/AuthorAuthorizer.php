<?php

namespace App\JsonApi\V2\Authors;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LaravelJsonApi\Contracts\Auth\Authorizer;

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
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return Gate::inspect('show'.ucfirst($fieldName), $model);
    }

    /**
     * Authorize the show-relationship controller action.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return Gate::inspect('show'.ucfirst($fieldName), $model);
    }

    /**
     * Authorize the update-relationship controller action.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
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
}
