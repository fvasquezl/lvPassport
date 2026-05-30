<?php

namespace App\JsonApi\V2\Articles;

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
            return false; // 401
        }

        $gate = Gate::inspect('create', $modelClass);
        if ($gate->denied()) {
            return $gate; // 403
        }

        $authorId = $request->input('data.relationships.authors.data.id');
        if ($authorId !== null && (string) $request->user()->getRouteKey() !== (string) $authorId) {
            return Response::deny(); // 403
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
        return Gate::inspect('update'.ucfirst($fieldName), $model);
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
