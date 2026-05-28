<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use JsonApi;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Signature('generate:roles')]
#[Description('Generate Spatie roles (admin/editor/viewer) with their permissions for all registered JSON:API resources')]
class GenerateRoles extends Command
{
    public function handle(): int
    {
        $types = JsonApi::server('v1')->schemas()->types();

        foreach ($types as $type) {
            foreach (GeneratePermissions::ABILITIES as $ability) {
                Permission::findOrCreate("{$type}:{$ability}", 'api');
            }
        }

        foreach (GeneratePermissions::RELATIONSHIP_PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'api');
        }

        $readOnly = collect($types)
            ->flatMap(fn (string $type) => ["{$type}:index", "{$type}:show"])
            ->all();

        $adminPermissions = collect($types)
            ->crossJoin(GeneratePermissions::ABILITIES)
            ->map(fn (array $pair) => "{$pair[0]}:{$pair[1]}")
            ->merge(GeneratePermissions::RELATIONSHIP_PERMISSIONS)
            ->all();

        $roles = [
            'admin' => $adminPermissions,
            'editor' => [
                ...$readOnly,
                'articles:store',
                'articles:update',
            ],
            'viewer' => $readOnly,
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::findOrCreate($name, 'api');
            $role->syncPermissions($permissions);
            $this->line("  {$name}: ".count($permissions).' permissions');
        }

        Role::findOrCreate('super-admin', 'api');
        $this->line('  super-admin: bypass (no permissions needed)');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Roles generated!');

        return self::SUCCESS;
    }
}
