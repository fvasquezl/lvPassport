<?php

namespace App\JsonApi\V1\Permissions;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Spatie\Permission\Models\Permission;

class PermissionSchema extends Schema
{
    public static string $model = Permission::class;

    protected $type = 'permissions';

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function authorizer(): string
    {
        return PermissionAuthorizer::class;
    }
}
