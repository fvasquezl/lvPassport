<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Signature('role:assign {role} {email}')]
#[Description('Assign a role to a user by email')]
class AssignRole extends Command
{
    public function handle(): int
    {
        $roleName = $this->argument('role');
        $email = $this->argument('email');

        $role = Role::where('name', $roleName)->where('guard_name', 'api')->first();

        if (! $role) {
            $this->error("Role [{$roleName}] not found.");
            $this->line("Run <info>php artisan role:create {$roleName}</info> first.");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Role [{$roleName}] assigned to [{$email}].");

        return self::SUCCESS;
    }
}
