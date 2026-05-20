<?php

namespace App\JsonApi\V1\Articles;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ArticleAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return Gate::inspect('viewAny', $modelClass);
    }

    /**
     * Authorize the store controller action.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        if (! $request->user()) {
            return false;
        }
        if ($request->has('data.relationships.authors')) {
            return Gate::inspect('create', $modelClass);
        }

        return true;
    }

    /**
     * Authorize the show controller action.
     */
    public function show(Request $request, object $model): bool|Response
    {
        return Gate::inspect('view', $model);
    }

    /**
     * Authorize the update controller action.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return Gate::inspect('update', $model);
    }

    /**
     * Authorize the destroy controller action.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return Gate::inspect('delete', $model);
    }

    /**
     * Authorize the show-related controller action
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        // TODO: Implement showRelated() method.
    }

    /**
     * Authorize the show-relationship controller action.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        // TODO: Implement showRelationship() method.
    }

    /**
     * Authorize the update-relationship controller action.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return Gate::inspect('update'.ucfirst($fieldName), $model);
    }

    /**
     * Authorize the attach-relationship controller action.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        // TODO: Implement attachRelationship() method.
    }

    /**
     * Authorize the detach-relationship controller action.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        // TODO: Implement detachRelationship() method.
    }
}
