<?php

namespace App\JsonApi\V2\Authors;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class AuthorRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            // Authors are read-only in the API; no write rules needed.
        ];
    }
}
