<?php

use App\Console\Commands\GeneratePermissions;
use Spatie\Permission\Models\Permission;

it('generates permissions for all registered api resources', function () {
    $this->artisan('generate:permissions')
        ->expectsOutput('Permissions generated!')
        ->assertSuccessful();

    $types = collect(JsonApi::server('v1')->schemas()->types());

    $expected = $types
        ->crossJoin(GeneratePermissions::ABILITIES)
        ->map(fn (array $pair) => "{$pair[0]}:{$pair[1]}")
        ->all();

    expect(Permission::query()->pluck('name')->all())
        ->toContain(...$expected);
});

it('is idempotent: running twice does not duplicate permissions', function () {
    $this->artisan('generate:permissions')->assertSuccessful();
    $countAfterFirst = Permission::query()->count();

    $this->artisan('generate:permissions')->assertSuccessful();

    expect(Permission::query()->count())->toBe($countAfterFirst);
});

it('generates relationship-specific permissions for articles', function () {
    $this->artisan('generate:permissions')
        ->assertSuccessful();

    $names = Permission::query()->pluck('name')->all();

    expect($names)
        ->toContain('articles:update-authors')
        ->toContain('articles:update-categories');
});

it('generates the read scope permission', function () {
    $this->artisan('generate:permissions')
        ->assertSuccessful();

    expect(Permission::where('name', 'read')->where('guard_name', 'api')->exists())
        ->toBeTrue();
});
