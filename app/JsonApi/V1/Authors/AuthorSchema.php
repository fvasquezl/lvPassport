<?php

namespace App\JsonApi\V1\Authors;

use App\Models\User;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Schema;

class AuthorSchema extends Schema
{
    public static string $model = User::class;

    public function fields(): array
    {
        return [
            ID::make()->uuid(),
            Str::make('name'),
            Str::make('email'),
            Str::make('password'),
            DateTime::make('createdAt')->readOnly(),
            DateTime::make('updatedAt')->readOnly(),
        ];
    }
}
