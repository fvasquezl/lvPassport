<?php

namespace App\JsonApi\V1\Articles;

use App\Models\Article;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class ArticleSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Article::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make()->matchAs('[a-z0-9]+(?:-[a-z0-9]+)*'),
            Str::make('title')->sortable(),
            Str::make('slug')->sortable(),
            Str::make('content')->sortable(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            BelongsTo::make('categories', 'category'),
            BelongsTo::make('authors', 'user')
                ->type('authors'),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Scope::make('categories'),
            Scope::make('authors'),
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
        return ArticleAuthorizer::class;
    }
}
