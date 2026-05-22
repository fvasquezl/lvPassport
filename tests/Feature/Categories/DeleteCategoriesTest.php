<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users cannot delete categories', function () {

    $category = Category::factory()->create();

    $this->jsonApi()
        ->delete(route('api.v1.categories.destroy', $category))
        ->assertUnauthorized(); // 401
});

it('authenticated users can delete categories', function () {

    $category = Category::factory()->create();

    Passport::actingAs(userWithPermission('categories:delete'));

    $this->jsonApi()
        ->delete(route('api.v1.categories.destroy', $category))
        ->assertNoContent(); // 204

});
