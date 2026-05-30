<?php

use App\JsonApi\V2\Server;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;

/**
 * Phase 1: Infrastructure — V2 server, routes, directory structure.
 *
 * These tests verify that V2 routes exist and return correct responses
 * without breaking V1 routes.
 */

// ─── Task 1.1 / 1.2: V2 Server registration ──────────────────────────────────

it('V2 Server class exists', function () {
    expect(class_exists(Server::class))->toBeTrue();
});

// ─── Task 1.3: V2 plain HTTP routes ──────────────────────────────────────────

it('V2 login route is registered as api.v2.login', function () {
    expect(Route::has('api.v2.login'))->toBeTrue();
});

it('V2 logout route is registered as api.v2.logout', function () {
    expect(Route::has('api.v2.logout'))->toBeTrue();
});

it('V2 user route is registered as api.v2.user', function () {
    expect(Route::has('api.v2.user'))->toBeTrue();
});

it('V2 login route is POST /api/v2/login', function () {
    $route = Route::getRoutes()->getByName('api.v2.login');
    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('api/v2/login')
        ->and($route->methods())->toContain('POST');
});

it('V2 logout route is POST /api/v2/logout', function () {
    $route = Route::getRoutes()->getByName('api.v2.logout');
    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('api/v2/logout')
        ->and($route->methods())->toContain('POST');
});

it('V2 user route is GET /api/v2/user', function () {
    $route = Route::getRoutes()->getByName('api.v2.user');
    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('api/v2/user')
        ->and($route->methods())->toContain('GET');
});

// ─── Task 1.4: V2 JSON:API resource routes ───────────────────────────────────

it('V2 articles index route is registered', function () {
    expect(Route::has('api.v2.articles.index'))->toBeTrue();
});

it('V2 authors index route is registered', function () {
    expect(Route::has('api.v2.authors.index'))->toBeTrue();
});

it('V2 categories index route is registered', function () {
    expect(Route::has('api.v2.categories.index'))->toBeTrue();
});

// ─── V1 routes remain untouched ──────────────────────────────────────────────

it('V1 login route is still registered', function () {
    expect(Route::has('api.v1.login'))->toBeTrue();
});

it('V1 logout route is still registered', function () {
    expect(Route::has('api.v1.logout'))->toBeTrue();
});

it('V1 user route is still registered', function () {
    expect(Route::has('api.v1.user'))->toBeTrue();
});

it('V1 articles index route is still registered', function () {
    expect(Route::has('api.v1.articles.index'))->toBeTrue();
});

// ─── Task 1.5: read scope registered ─────────────────────────────────────────

it('read scope is declared in Passport::tokensCan', function () {
    /** @var PassportServiceProvider $passport */
    $scopes = Passport::scopes()->pluck('id')->toArray();
    expect($scopes)->toContain('read');
});
