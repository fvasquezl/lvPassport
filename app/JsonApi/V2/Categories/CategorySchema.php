<?php

namespace App\JsonApi\V2\Categories;

use App\Models\Category;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class CategorySchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Category::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make()->matchAs('[a-z0-9]+(?:-[a-z0-9]+)*'),
            Str::make('name')->sortable(),
            Str::make('slug')->sortable(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            HasMany::make('articles'),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Scope::make('name'),
            Scope::make('slug'),
            Scope::make('search'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function authorizer(): string
    {
        return CategoryAuthorizer::class;
    }
}
