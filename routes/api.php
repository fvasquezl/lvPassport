<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::post('login', LoginController::class)->name('login');
});

Route::prefix('v1')->name('v1.')->middleware('auth:api')->group(function () {
    Route::get('user', UserController::class)->name('user');
    Route::post('logout', LogoutController::class)->name('logout');
});

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('articles', JsonApiController::class)
            ->middleware([
                '*' => [],
                'store' => 'auth:api',
                'update' => 'auth:api',
                'destroy' => 'auth:api',
            ])
            ->relationships(function ($relationships) {
                $relationships
                    ->hasOne('authors')
                    ->middleware([
                        '*' => [],
                        'update' => 'auth:api',
                    ]);
                $relationships
                    ->hasOne('categories')
                    ->middleware([
                        '*' => [],
                        'update' => 'auth:api',
                    ]);
            });

        // Authors — solo lectura
        $server->resource('authors', JsonApiController::class)
            ->relationships(function ($server) {
                $server->hasMany('articles')->readOnly();
            })->only('index', 'show');

        $server->resource('categories', JsonApiController::class)
            ->middleware([
                '*' => [],
                'store' => 'auth:api',
                'update' => 'auth:api',
                'destroy' => 'auth:api',
            ])
            ->relationships(function ($server) {
                $server->hasMany('articles')->readOnly();
            });

    });
