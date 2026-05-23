<?php

namespace App\JsonApi\V1\Authors;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class AuthorRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            // @TODO
        ];
    }
}
