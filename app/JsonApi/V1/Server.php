<?php

namespace App\JsonApi\V1;

use App\JsonApi\V1\Articles\ArticleSchema;
use App\JsonApi\V1\Authors\AuthorSchema;
use App\JsonApi\V1\Categories\CategorySchema;
use App\JsonApi\V1\Permissions\PermissionSchema;
use App\JsonApi\V1\Roles\RoleSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = '/api/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     */
    public function serving(): void
    {
        // no-op
    }

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        return [
            ArticleSchema::class,
            AuthorSchema::class,
            CategorySchema::class,
            PermissionSchema::class,
            RoleSchema::class,
        ];
    }
}
