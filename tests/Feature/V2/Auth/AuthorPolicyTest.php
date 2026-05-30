<?php

use App\Models\User;
use App\Policies\AuthorPolicy;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

// ─── viewAny: tokenCan('read') ────────────────────────────────────────────────

it('allows viewAny when token has read scope', function () {
    $user = User::factory()->create();

    Passport::actingAs($user, ['read']);

    expect(Gate::allows('viewAny', User::class))->toBeTrue();
});

it('denies viewAny when token has no read scope', function () {
    $user = User::factory()->create();

    Passport::actingAs($user, []);

    // super-admin bypass does not apply to regular users
    // Gate::before only fires for super-admin role
    $policy = new AuthorPolicy;
    expect($policy->viewAny($user))->toBeFalse();
});

// ─── view: tokenCan('read') ───────────────────────────────────────────────────

it('allows view when token has read scope', function () {
    $user = User::factory()->create();
    $author = User::factory()->create();

    Passport::actingAs($user, ['read']);

    expect(Gate::allows('view', $author))->toBeTrue();
});

it('denies view when token has no read scope', function () {
    $user = User::factory()->create();
    $author = User::factory()->create();

    Passport::actingAs($user, []);

    $policy = new AuthorPolicy;
    expect($policy->view($user, $author))->toBeFalse();
});

// ─── Write operations always return false ─────────────────────────────────────

it('denies create for any user', function () {
    $user = User::factory()->create();
    $policy = new AuthorPolicy;

    expect($policy->create($user))->toBeFalse();
});

it('denies update for any user', function () {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $policy = new AuthorPolicy;

    expect($policy->update($user, $author))->toBeFalse();
});

it('denies delete for any user', function () {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $policy = new AuthorPolicy;

    expect($policy->delete($user, $author))->toBeFalse();
});
