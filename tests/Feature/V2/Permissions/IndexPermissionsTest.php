<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('permissions:index', 'api');
    Permission::findOrCreate('permissions:show', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('a super-admin can list permissions', function () {
    Permission::findOrCreate('articles:store', 'api');
    Permission::findOrCreate('articles:index', 'api');

    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->get(route('api.v2.permissions.index'))
        ->assertOk()
        ->assertJsonPath('data.0.type', 'permissions');
});

it('guests cannot list permissions', function () {
    $this->jsonApi()
        ->get(route('api.v2.permissions.index'))
        ->assertUnauthorized();
});

it('non-admin users cannot list permissions', function () {
    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v2.permissions.index'))
        ->assertForbidden();
});

it('the permissions endpoint does not expose write routes', function () {
    expect(Route::has('api.v2.permissions.store'))->toBeFalse();
    expect(Route::has('api.v2.permissions.update'))->toBeFalse();
    expect(Route::has('api.v2.permissions.destroy'))->toBeFalse();
});
