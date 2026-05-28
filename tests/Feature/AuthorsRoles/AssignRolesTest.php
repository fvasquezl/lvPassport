<?php

use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('authors:update-roles', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('a super-admin can assign a role to another user', function () {
    $role = Role::findOrCreate('capturista', 'api');
    $target = User::factory()->create();

    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->withData([['type' => 'roles', 'id' => (string) $role->id]])
        ->patch(route('api.v1.authors.roles.update', $target))
        ->assertSuccessful();

    expect($target->fresh()->hasRole('capturista'))->toBeTrue();
});

it('a super-admin can replace another user\'s roles', function () {
    $oldRole = Role::findOrCreate('viewer', 'api');
    $newRole = Role::findOrCreate('editor', 'api');
    $target = User::factory()->create();
    $target->assignRole($oldRole);

    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->withData([['type' => 'roles', 'id' => (string) $newRole->id]])
        ->patch(route('api.v1.authors.roles.update', $target))
        ->assertSuccessful();

    $target->refresh();
    expect($target->hasRole('editor'))->toBeTrue();
    expect($target->hasRole('viewer'))->toBeFalse();
});

it('a super-admin cannot remove the super-admin role from themselves', function () {
    $editor = Role::findOrCreate('editor', 'api');

    $actor = userWithRole('super-admin', []);

    Passport::actingAs($actor);

    $this->jsonApi()
        ->withData([['type' => 'roles', 'id' => (string) $editor->id]])
        ->patch(route('api.v1.authors.roles.update', $actor))
        ->assertForbidden();

    expect($actor->fresh()->hasRole('super-admin'))->toBeTrue();
});

it('a super-admin can demote a different super-admin user', function () {
    $editor = Role::findOrCreate('editor', 'api');

    $actor = userWithRole('super-admin', []);
    $otherSuper = userWithRole('super-admin', [], User::factory()->create());

    Passport::actingAs($actor);

    $this->jsonApi()
        ->withData([['type' => 'roles', 'id' => (string) $editor->id]])
        ->patch(route('api.v1.authors.roles.update', $otherSuper))
        ->assertSuccessful();

    $otherSuper->refresh();
    expect($otherSuper->hasRole('super-admin'))->toBeFalse();
    expect($otherSuper->hasRole('editor'))->toBeTrue();
});

it('a non-super-admin user without authors:update-roles cannot assign roles', function () {
    $role = Role::findOrCreate('viewer', 'api');
    $target = User::factory()->create();

    Passport::actingAs(User::factory()->create(), ['authors:update-roles']);

    $this->jsonApi()
        ->withData([['type' => 'roles', 'id' => (string) $role->id]])
        ->patch(route('api.v1.authors.roles.update', $target))
        ->assertForbidden();
});

it('guests cannot assign roles', function () {
    $role = Role::findOrCreate('viewer', 'api');
    $target = User::factory()->create();

    $this->jsonApi()
        ->withData([['type' => 'roles', 'id' => (string) $role->id]])
        ->patch(route('api.v1.authors.roles.update', $target))
        ->assertUnauthorized();
});
