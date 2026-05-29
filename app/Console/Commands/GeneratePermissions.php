<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use JsonApi;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

#[Signature('generate:permissions')]
#[Description('Generate Spatie permissions for all registered JSON:API resources')]
class GeneratePermissions extends Command
{
    /** @var array<int, string> */
    public const array ABILITIES = ['index', 'show', 'store', 'update', 'delete'];

    /** @var array<int, string> */
    public const array RELATIONSHIP_PERMISSIONS = [
        'articles:update-authors',
        'articles:update-categories',
    ];

    public function handle(): int
    {
        $types = JsonApi::server('v1')->schemas()->types();

        foreach ($types as $type) {
            foreach (self::ABILITIES as $ability) {
                Permission::findOrCreate("{$type}:{$ability}", 'api');
            }
        }

        foreach (self::RELATIONSHIP_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        Permission::findOrCreate('read', 'api');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Permissions generated!');

        return self::SUCCESS;
    }
}
