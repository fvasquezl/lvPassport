<?php

use App\Models\Article;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('articles:store', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot create articles', function () {
    $data = jsonData(
        Article::factory()->make(),
    );

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.articles.store'))
        ->assertUnauthorized(); // 401

    $this->assertDatabaseEmpty('articles');

});
