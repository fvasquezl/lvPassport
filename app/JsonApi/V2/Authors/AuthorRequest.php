<?php

namespace App\JsonApi\V2\Authors;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class AuthorRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * Author attributes are read-only; only the roles relationship is writable
     * (assigning roles to an author via PATCH .../relationships/roles).
     */
    public function rules(): array
    {
        return [
            'roles' => [JsonApiRule::toMany()],
        ];
    }
}
