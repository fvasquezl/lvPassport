<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\AuthorPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        Gate::policy(User::class, AuthorPolicy::class);

        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);

        Role::creating(function (Role $role): void {
            if (empty($role->guard_name)) {
                $role->guard_name = 'api';
            }
        });

        Passport::tokensCan([
            // General
            'read' => 'Read-only access (default V2 scope)',

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

            // Comments
            'comments:index' => 'List comments',
            'comments:show' => 'View a comment',
            'comments:store' => 'Create a comment',
            'comments:update' => 'Update a comment',
            'comments:delete' => 'Delete a comment',

            // Authors
            'authors:index' => 'List authors',
            'authors:show' => 'View an author',
            'authors:show-articles' => 'View an author\'s articles',
            'authors:show-roles' => 'View an author\'s roles',
            'authors:update-roles' => 'Assign roles to an author',

            // Roles
            'roles:index' => 'List roles',
            'roles:show' => 'View a role',
            'roles:store' => 'Create a role',
            'roles:update' => 'Update a role',
            'roles:delete' => 'Delete a role',

            // Permissions
            'permissions:index' => 'List permissions',
            'permissions:show' => 'View a permission',
        ]);
    }
}
