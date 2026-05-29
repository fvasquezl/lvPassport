<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can paginate categories', function () {
    Category::factory()->count(10)->create();

    $this->jsonApi()
        ->page(['size' => 2, 'number' => 1])
        ->get(route('api.v2.categories.index'))
        ->assertOk();
});

it('authenticated users without scope can paginate categories', function () {
    Category::factory()->count(10)->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->page(['size' => 2, 'number' => 1])
        ->get(route('api.v2.categories.index'))
        ->assertOk();
});

it('can paginate categories', function () {
    Category::factory()->count(10)->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $response = $this->jsonApi()
        ->page(['size' => 2, 'number' => 3])
        ->get(route('api.v2.categories.index'));

    $response->assertJsonStructure([
        'links' => ['first', 'last', 'prev', 'next'],
    ]);

    $response->assertJsonFragment([
        'first' => route('api.v2.categories.index', ['page[number]' => 1, 'page[size]' => 2]),
        'last' => route('api.v2.categories.index', ['page[number]' => 5, 'page[size]' => 2]),
        'prev' => route('api.v2.categories.index', ['page[number]' => 2, 'page[size]' => 2]),
        'next' => route('api.v2.categories.index', ['page[number]' => 4, 'page[size]' => 2]),
    ]);
});
