<?php

namespace App\JsonApi\V1\Roles;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Spatie\Permission\Models\Role;

class RoleSchema extends Schema
{
    public static string $model = Role::class;

    protected $type = 'roles';

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            BelongsToMany::make('permissions'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function authorizer(): string
    {
        return RoleAuthorizer::class;
    }
}
