<?php

namespace App\JsonApi\V2\Comments;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CommentAuthorizer implements Authorizer
{
    /**
     * Reads are public.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return true;
    }

    /**
     * Create requires scope + permission (via policy) and that the declared
     * author is the authenticated user (ownership).
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

        $authorId = $request->input('data.relationships.author.data.id');
        if ($authorId !== null && (string) $request->user()->getRouteKey() !== (string) $authorId) {
            return Response::deny(); // 403 — cannot comment on behalf of another user
        }

        return true;
    }

    /**
     * Reads are public.
     */
    public function show(Request $request, object $model): bool|Response
    {
        return true;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return Gate::inspect('update', $model);
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return Gate::inspect('delete', $model);
    }

    /**
     * Relationship reads (author, article) are public.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
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
