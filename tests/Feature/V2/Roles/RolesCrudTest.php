<?php

use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    foreach (['roles:index', 'roles:show', 'roles:store', 'roles:update', 'roles:delete'] as $name) {
        Permission::findOrCreate($name, 'api');
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('a super-admin can list roles', function () {
    Role::findOrCreate('a-role', 'api');
    Role::findOrCreate('another-role', 'api');

    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->get(route('api.v2.roles.index'))
        ->assertOk()
        ->assertJsonPath('data.0.type', 'roles');
});

it('a super-admin can create a role', function () {
    Passport::actingAs(userWithRole('super-admin', []));

    $response = $this->jsonApi()
        ->withData([
            'type' => 'roles',
            'attributes' => ['name' => 'new-role'],
        ])
        ->post(route('api.v2.roles.store'))
        ->assertCreated();

    expect($response->json('data.attributes.name'))->toBe('new-role');
    $this->assertDatabaseHas('roles', ['name' => 'new-role', 'guard_name' => 'api']);
});

it('a super-admin can update a non-super-admin role', function () {
    $role = Role::findOrCreate('to-rename', 'api');

    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->withData([
            'type' => 'roles',
            'id' => (string) $role->id,
            'attributes' => ['name' => 'renamed'],
        ])
        ->patch(route('api.v2.roles.update', $role))
        ->assertOk();

    $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'renamed']);
});

it('a super-admin can delete a non-super-admin role', function () {
    $role = Role::findOrCreate('disposable', 'api');

    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->delete(route('api.v2.roles.destroy', $role))
        ->assertNoContent();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

it('roles created via API default to guard_name api', function () {
    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->withData([
            'type' => 'roles',
            'attributes' => ['name' => 'guard-test'],
        ])
        ->post(route('api.v2.roles.store'))
        ->assertCreated();

    $this->assertDatabaseHas('roles', ['name' => 'guard-test', 'guard_name' => 'api']);
});

it('the super-admin role cannot be deleted', function () {
    $superAdmin = Role::findOrCreate('super-admin', 'api');

    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->delete(route('api.v2.roles.destroy', $superAdmin))
        ->assertForbidden();

    $this->assertDatabaseHas('roles', ['id' => $superAdmin->id, 'name' => 'super-admin']);
});

it('the super-admin role cannot be updated', function () {
    $superAdmin = Role::findOrCreate('super-admin', 'api');

    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->withData([
            'type' => 'roles',
            'id' => (string) $superAdmin->id,
            'attributes' => ['name' => 'sneaky-rename'],
        ])
        ->patch(route('api.v2.roles.update', $superAdmin))
        ->assertForbidden();

    $this->assertDatabaseHas('roles', ['id' => $superAdmin->id, 'name' => 'super-admin']);
});

it('guests cannot list roles', function () {
    $this->jsonApi()
        ->get(route('api.v2.roles.index'))
        ->assertUnauthorized();
});

it('non-admin users without roles permission get 403 on listing', function () {
    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v2.roles.index'))
        ->assertForbidden();
});

it('non-admin users cannot create roles', function () {
    Passport::actingAs(User::factory()->create(), ['roles:store']);

    $this->jsonApi()
        ->withData([
            'type' => 'roles',
            'attributes' => ['name' => 'sneak'],
        ])
        ->post(route('api.v2.roles.store'))
        ->assertForbidden();

    $this->assertDatabaseMissing('roles', ['name' => 'sneak']);
});

it('admin role members can list roles', function () {
    Passport::actingAs(
        userWithRole('admin', ['roles:index']),
        ['roles:index']
    );

    $this->jsonApi()
        ->get(route('api.v2.roles.index'))
        ->assertOk();
});
