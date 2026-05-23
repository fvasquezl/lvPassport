<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::tokensCan([
            // Articles
            'articles:index' => 'List articles',
            'articles:show' => 'View an article',
            'articles:store' => 'Create an article',
            'articles:update' => 'Update an article',
            'articles:delete' => 'Delete an article',
            'articles:show-authors' => 'View an article\'s author',
            'articles:show-categories' => 'View an article\'s category',
            'articles:update-authors' => 'Change an article\'s author',
            'articles:update-categories' => 'Change an article\'s category',

            // Categories
            'categories:index' => 'List categories',
            'categories:show' => 'View a category',
            'categories:store' => 'Create a category',
            'categories:update' => 'Update a category',
            'categories:delete' => 'Delete a category',
            'categories:show-articles' => 'View a category\'s articles',

            // Authors
            'authors:index' => 'List authors',
            'authors:show' => 'View an author',
            'authors:show-articles' => 'View an author\'s articles',
        ]);
    }
}
