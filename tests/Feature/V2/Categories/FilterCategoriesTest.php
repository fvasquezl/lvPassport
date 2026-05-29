<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can filter categories', function () {
    Category::factory()->create();

    $this->jsonApi()
        ->filter(['name' => 'whatever'])
        ->get(route('api.v2.categories.index'))
        ->assertOk();
});

it('authenticated users without scope can filter categories', function () {
    Category::factory()->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->filter(['name' => 'whatever'])
        ->get(route('api.v2.categories.index'))
        ->assertOk();
});

it('can filter categories by name', function () {
    Category::factory()->create(['name' => 'Laravel']);
    Category::factory()->create(['name' => 'PHP']);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['name' => 'Laravel'])
        ->get(route('api.v2.categories.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Laravel'])
        ->assertJsonMissing(['name' => 'PHP']);
});

it('can filter categories by slug', function () {
    Category::factory()->create(['slug' => 'laravel-category']);
    Category::factory()->create(['slug' => 'php-category']);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['slug' => 'laravel-category'])
        ->get(route('api.v2.categories.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['slug' => 'laravel-category'])
        ->assertJsonMissing(['slug' => 'php-category']);
});

it('can search categories by name', function () {
    Category::factory()->create(['name' => 'Laravel Tips']);
    Category::factory()->create(['name' => 'Vue Tips']);
    Category::factory()->create(['name' => 'PHP Basics']);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['search' => 'Tips'])
        ->get(route('api.v2.categories.index'))
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['name' => 'Laravel Tips'])
        ->assertJsonFragment(['name' => 'Vue Tips'])
        ->assertJsonMissing(['name' => 'PHP Basics']);
});

it('cannot filter categories by unknown filters', function () {
    Category::factory()->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['unknown' => 'value'])
        ->get(route('api.v2.categories.index'))
        ->assertBadRequest();
});
