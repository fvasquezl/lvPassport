<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Signature('role:create {name} {--permissions=}')]
#[Description('Create a role and optionally assign permissions to it')]
class CreateRole extends Command
{
    public function handle(): int
    {
        $name = $this->argument('name');

        $permissionNames = collect(explode(',', $this->option('permissions') ?? ''))
            ->map(fn (string $p) => trim($p))
            ->filter()
            ->values();

        if ($permissionNames->isNotEmpty()) {
            $missing = $permissionNames->reject(
                fn (string $permission) => Permission::where('name', $permission)
                    ->where('guard_name', 'api')
                    ->exists()
            );

            if ($missing->isNotEmpty()) {
                $this->error("Permissions not found: {$missing->join(', ')}");
                $this->line('Run <info>php artisan generate:permissions</info> first.');

                return self::FAILURE;
            }
        }

        $role = Role::findOrCreate($name, 'api');

        if ($permissionNames->isNotEmpty()) {
            $role->givePermissionTo($permissionNames->all());
            $this->line("Permissions assigned: {$permissionNames->join(', ')}");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Role [{$name}] ready.");

        return self::SUCCESS;
    }
}
