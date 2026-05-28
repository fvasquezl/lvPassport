<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Signature('app:make-super-admin {email : Email of an existing user}')]
#[Description('Assign the super-admin role to an existing user (creates the role if missing)')]
class MakeSuperAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        $role = Role::findOrCreate('super-admin', 'api');
        $user->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("User {$user->email} ({$user->id}) is now super-admin.");

        return self::SUCCESS;
    }
}
