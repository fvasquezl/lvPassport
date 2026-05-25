<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('assigns a role to a user by email', function () {
    Role::findOrCreate('editor', 'api');
    $user = User::factory()->create();

    $this->artisan("role:assign editor {$user->email}")
        ->expectsOutput("Role [editor] assigned to [{$user->email}].")
        ->assertSuccessful();

    expect($user->fresh()->hasRole('editor'))->toBeTrue();
});

it('fails when the role does not exist', function () {
    $user = User::factory()->create();

    $this->artisan("role:assign editor {$user->email}")
        ->expectsOutput('Role [editor] not found.')
        ->assertFailed();

    expect($user->fresh()->roles)->toBeEmpty();
});

it('fails when the user email does not exist', function () {
    Role::findOrCreate('editor', 'api');

    $this->artisan('role:assign editor nobody@example.com')
        ->expectsOutput('No user found with email [nobody@example.com].')
        ->assertFailed();
});

it('is idempotent: assigning the same role twice does not duplicate it', function () {
    Role::findOrCreate('editor', 'api');
    $user = User::factory()->create();

    $this->artisan("role:assign editor {$user->email}")->assertSuccessful();
    $this->artisan("role:assign editor {$user->email}")->assertSuccessful();

    expect($user->fresh()->roles()->count())->toBe(1);
});

it('can assign multiple different roles to the same user', function () {
    Role::findOrCreate('editor', 'api');
    Role::findOrCreate('viewer', 'api');
    $user = User::factory()->create();

    $this->artisan("role:assign editor {$user->email}")->assertSuccessful();
    $this->artisan("role:assign viewer {$user->email}")->assertSuccessful();

    expect($user->fresh()->roles()->count())->toBe(2);
});
