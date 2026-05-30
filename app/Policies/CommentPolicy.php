<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Reads are public.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Reads are public.
     */
    public function view(?User $user, Comment $comment): bool
    {
        return true;
    }

    /**
     * Scope + permission (ownership of the author is enforced in the authorizer
     * against the request payload).
     */
    public function create(User $user): bool
    {
        return $user->tokenCan('comments:store')
            && $user->hasPermissionTo('comments:store');
    }

    /**
     * Scope + permission + ownership.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->tokenCan('comments:update')
            && $user->hasPermissionTo('comments:update')
            && $comment->user->is($user);
    }

    /**
     * Scope + permission + ownership.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->tokenCan('comments:delete')
            && $user->hasPermissionTo('comments:delete')
            && $comment->user->is($user);
    }
}
