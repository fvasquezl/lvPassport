<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:api')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('articles', JsonApiController::class)
            ->only('store', 'update', 'destroy')
            ->relationships(function ($relationships) {
                $relationships->hasOne('categories')->only('show', 'update');
                $relationships->hasOne('authors')->only('show', 'update');
            });

    });
