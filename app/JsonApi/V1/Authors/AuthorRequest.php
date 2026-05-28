<?php

namespace App\JsonApi\V1\Authors;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class AuthorRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'roles' => [JsonApiRule::toMany()],
        ];
    }
}
