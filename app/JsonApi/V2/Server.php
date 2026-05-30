<?php

namespace App\JsonApi\V2;

use App\JsonApi\V2\Articles\ArticleSchema;
use App\JsonApi\V2\Authors\AuthorSchema;
use App\JsonApi\V2\Categories\CategorySchema;
use App\JsonApi\V2\Permissions\PermissionSchema;
use App\JsonApi\V2\Roles\RoleSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

/**
 * V2 JSON:API Server.
 */
class Server extends BaseServer
{
    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = '/api/v2';

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
            RoleSchema::class,
            PermissionSchema::class,
        ];
    }
}
