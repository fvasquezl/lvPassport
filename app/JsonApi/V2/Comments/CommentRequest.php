<?php

namespace App\JsonApi\V2\Comments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class CommentRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * On update, laravel-json-api fills omitted fields with their existing
     * values, so `required` rules hold for both create and partial update.
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'author' => ['required', JsonApiRule::toOne()],
            'article' => ['required', JsonApiRule::toOne()],
        ];
    }
}
