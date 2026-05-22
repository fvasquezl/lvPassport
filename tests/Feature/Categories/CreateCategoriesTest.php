<?php

use App\Models\Category;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->user = userWithPermission('categories:store');
});

it('guest users cannot create categories', function () {

    $data = jsonData(Category::factory()->make());

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnauthorized(); // 401

    $this->assertDatabaseCount('categories', 0);
});

it('authenticated users can create categories', function () {

    $data = jsonData(
        $category = Category::factory()->make()
    );

    Passport::actingAs($this->user, ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertCreated();  // 201

    $this->assertDatabaseHas('categories', [
        $category->getRouteKeyName() => $category->getRouteKey(),
    ]);
});

it('category name is required', function () {

    $data = jsonData(
        $category = Category::factory()->make(['name' => ''])
    );

    Passport::actingAs($this->user,['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/name');

    $this->assertDatabaseMissing('categories', [
        $category->getRouteKeyName() => $category->getRouteKey(),
    ]);
});

it('slug is required', function () {

    $data = jsonData(
        $category = Category::factory()->make(['slug' => ''])
    );

    Passport::actingAs($this->user,['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/slug');

    $this->assertDatabaseMissing('categories', [
        $category->getRouteKeyName() => $category->getRouteKey(),
    ]);
});

it('slug must be unique', function () {

    Category::factory()->create(['slug' => 'same-slug']);

    $data = jsonData(
        $category = Category::factory()->make(['slug' => 'same-slug'])
    );

    Passport::actingAs($this->user,['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/slug');

    $this->assertDatabaseCount('categories', 1);
});

it('slug must only contain letters numbers and dashes', function () {

    $data = jsonData(
        $category = Category::factory()->make(['slug' => '%$%#@'])
    );

    Passport::actingAs($this->user,['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/slug');

    $this->assertDatabaseMissing('categories', [
        $category->getRouteKeyName() => $category->getRouteKey(),
    ]);

});

it('slug must not contain underscores', function () {

    $data = jsonData(
        $category = Category::factory()->make(['slug' => 'with_underscores'])
    );

    Passport::actingAs($this->user,['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/slug');

    $this->assertDatabaseMissing('categories', [
        $category->getRouteKeyName() => $category->getRouteKey(),
    ]);

});

it('slug must not start with dashes', function () {

    $data = jsonData(
        $category = Category::factory()->make(['slug' => '-start-with-dash'])
    );

    Passport::actingAs($this->user,['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/slug');

    $this->assertDatabaseMissing('categories', [
        $category->getRouteKeyName() => $category->getRouteKey(),
    ]);
});

it('slug must not end with dashes', function () {

    $data = jsonData(
        $category = Category::factory()->make(['slug' => 'end-with-dash-'])
    );

    Passport::actingAs($this->user,['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/slug');

    $this->assertDatabaseMissing('categories', [
        $category->getRouteKeyName() => $category->getRouteKey(),
    ]);

});
