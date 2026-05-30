<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('categories:delete', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot delete categories', function () {
    $category = Category::factory()->create();

    $this->jsonApi()
        ->delete(route('api.v2.categories.destroy', $category))
        ->assertUnauthorized();

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

it('authenticated users without scope cannot delete categories', function () {
    $category = Category::factory()->create();

    $user = userWithPermission('categories:delete');
    Passport::actingAs($user);

    $this->jsonApi()
        ->delete(route('api.v2.categories.destroy', $category))
        ->assertForbidden();

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

it('authenticated users without permission cannot delete categories', function () {
    $category = Category::factory()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['categories:delete']);

    $this->jsonApi()
        ->delete(route('api.v2.categories.destroy', $category))
        ->assertForbidden();

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

it('users with permission can delete categories', function () {
    $category = Category::factory()->create();

    $user = userWithPermission('categories:delete');
    Passport::actingAs($user, ['categories:delete']);

    $this->jsonApi()
        ->delete(route('api.v2.categories.destroy', $category))
        ->assertNoContent();

    $this->assertModelMissing($category);
});
