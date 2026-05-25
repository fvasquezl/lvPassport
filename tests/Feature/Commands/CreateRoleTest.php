<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('creates a role', function () {
    $this->artisan('role:create editor')
        ->expectsOutput('Role [editor] ready.')
        ->assertSuccessful();

    expect(Role::where('name', 'editor')->where('guard_name', 'api')->exists())->toBeTrue();
});

it('creates a role with permissions', function () {
    Permission::findOrCreate('articles:store', 'api');
    Permission::findOrCreate('articles:update', 'api');

    $this->artisan('role:create editor --permissions=articles:store,articles:update')
        ->assertSuccessful();

    $role = Role::findByName('editor', 'api');

    expect($role->hasPermissionTo('articles:store'))->toBeTrue()
        ->and($role->hasPermissionTo('articles:update'))->toBeTrue();
});

it('creates a role without permissions for super-admin', function () {
    $this->artisan('role:create super-admin')
        ->assertSuccessful();

    $role = Role::findByName('super-admin', 'api');

    expect($role->permissions)->toBeEmpty();
});

it('fails when a permission does not exist', function () {
    $this->artisan('role:create editor --permissions=articles:store')
        ->expectsOutput('Permissions not found: articles:store')
        ->assertFailed();

    expect(Role::where('name', 'editor')->exists())->toBeFalse();
});

it('is idempotent: running twice does not duplicate the role', function () {
    $this->artisan('role:create editor')->assertSuccessful();
    $this->artisan('role:create editor')->assertSuccessful();

    expect(Role::where('name', 'editor')->where('guard_name', 'api')->count())->toBe(1);
});

it('is idempotent: assigning the same permissions twice does not duplicate them', function () {
    Permission::findOrCreate('articles:store', 'api');

    $this->artisan('role:create editor --permissions=articles:store')->assertSuccessful();
    $this->artisan('role:create editor --permissions=articles:store')->assertSuccessful();

    $role = Role::findByName('editor', 'api');

    expect($role->permissions()->count())->toBe(1);
});
