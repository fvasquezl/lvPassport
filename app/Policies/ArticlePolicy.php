<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->tokenCan('articles:index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Article $article): bool
    {
        return $user->tokenCan('articles:show');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {

        return $user->tokenCan('articles:store')
            && $user->hasPermissionTo('articles:store');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Article $article): bool
    {
        return $user->tokenCan('articles:update')
            && $user->hasPermissionTo('articles:update')
            && $article->user->is($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Article $article): bool
    {
        return $user->tokenCan('articles:delete')
            && $user->hasPermissionTo('articles:delete')
            && $article->user->is($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Article $article): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Article $article): bool
    {
        return false;
    }

    public function updateCategories(User $user, Article $article): bool
    {
        return $user->tokenCan('articles:update-categories')
            && $user->hasPermissionTo('articles:update-categories')
            && $article->user->is($user);
    }

    public function updateAuthors(User $user, Article $article): bool
    {
        return $user->tokenCan('articles:update-authors')
            && $user->hasPermissionTo('articles:update-authors')
            && $article->user->is($user);
    }

    public function showAuthors(User $user, Article $article): bool
    {
        return $user->tokenCan('articles:show-authors');
    }

    public function showCategories(User $user, Article $article): bool
    {
        return $user->tokenCan('articles:show-categories');
    }
}
