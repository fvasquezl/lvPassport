<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can sort categories', function () {
    Category::factory()->count(3)->create();

    $this->jsonApi()
        ->sort('name')
        ->get(route('api.v2.categories.index'))
        ->assertOk();
});

it('authenticated users without scope can sort categories', function () {
    Category::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->sort('name')
        ->get(route('api.v2.categories.index'))
        ->assertOk();
});

it('can sort categories by name', function (string $sort, array $expected) {
    Category::factory()->create(['name' => 'C Category']);
    Category::factory()->create(['name' => 'A Category']);
    Category::factory()->create(['name' => 'B Category']);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->sort($sort)
        ->get(route('api.v2.categories.index'))
        ->assertSeeInOrder($expected);
})
    ->with([
        'asc' => ['name', ['A Category', 'B Category', 'C Category']],
        'desc' => ['-name', ['C Category', 'B Category', 'A Category']],
    ]);

it('can sort categories by slug', function (string $sort, array $expected) {
    Category::factory()->create(['slug' => 'c-category']);
    Category::factory()->create(['slug' => 'a-category']);
    Category::factory()->create(['slug' => 'b-category']);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->sort($sort)
        ->get(route('api.v2.categories.index'))
        ->assertSeeInOrder($expected);
})
    ->with([
        'asc' => ['slug', ['a-category', 'b-category', 'c-category']],
        'desc' => ['-slug', ['c-category', 'b-category', 'a-category']],
    ]);

it('cannot sort categories by unknown fields', function () {
    Category::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->sort('unknown')
        ->get(route('api.v2.categories.index'))
        ->assertBadRequest();
});
