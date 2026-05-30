<?php

namespace App\JsonApi\V2\Authors;

use App\Models\User;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Schema;

class AuthorSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = User::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make()->uuid(),
            Str::make('name'),
            Str::make('email'),
            DateTime::make('createdAt')->readOnly(),
            DateTime::make('updatedAt')->readOnly(),
            HasMany::make('articles'),
        ];
    }

    public static function authorizer(): string
    {
        return AuthorAuthorizer::class;
    }
}
