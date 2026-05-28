<?php

namespace App\JsonApi\V1\Roles;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Spatie\Permission\Models\Role;

class RoleRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'alpha_dash',
                'max:125',
                Rule::unique(Role::class, 'name')
                    ->where('guard_name', 'api')
                    ->ignore($this->model()),
            ],
            'permissions' => [JsonApiRule::toMany()],
        ];
    }
}
