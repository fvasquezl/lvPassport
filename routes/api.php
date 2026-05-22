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
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('articles', JsonApiController::class)
            ->middleware([
                '*' => [],
                'store' => 'auth:api',
                'update' => 'auth:api',
                'destroy' => 'auth:api'
            ])
            ->relationships(function ($relationships) {
                $relationships
                    ->hasOne('authors')
                    ->middleware([
                        '*' =>[],
                        'update'=>'auth:api'
                    ]);
                $relationships
                    ->hasOne('categories')
                    ->middleware([
                        '*' =>[],
                        'update'=>'auth:api'
                    ]);
            });

//        // Authors — solo lectura
//        $server->resource('authors', JsonApiController::class)
//            ->relationships(function ($server) {
//                $server->hasMany('articles')->except('update', 'attach', 'detach');
//            })->only('index', 'show');
//
//        $server->resource('categories', JsonApiController::class)
//            ->relationships(function ($server) {
//                $server->hasMany('articles')->except('update', 'attach', 'detach');
//            });

    });





