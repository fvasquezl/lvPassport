<?php

namespace App\JsonApi\V1\Categories;

use App\Models\Category;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Schema;

class CategorySchema extends Schema
{
    public static string $model = Category::class;

    public function fields(): array
    {
        return [
            ID::make()->matchAs('[a-z0-9]+(?:-[a-z0-9]+)*'),
            Str::make('name'),
            Str::make('slug'),
            DateTime::make('createdAt')->readOnly(),
            DateTime::make('updatedAt')->readOnly(),
            HasMany::make('articles'),
        ];
    }
}
