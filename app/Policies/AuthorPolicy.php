<?php

namespace App\Policies;

use App\Models\User;

class AuthorPolicy
{
    /**
     * Determine whether the user can view any authors.
     *
     * Reads require a token with the 'read' scope. No permission check —
     * tokenCan() only, per V2 design decision.
     */
    public function viewAny(User $user): bool
    {
        return $user->tokenCan('read');
    }

    /**
     * Determine whether the user can view an author.
     *
     * Reads require a token with the 'read' scope. No permission check —
     * tokenCan() only, per V2 design decision.
     */
    public function view(User $user, User $author): bool
    {
        return $user->tokenCan('read');
    }

    /**
     * Authors cannot be created via the API.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Authors cannot be updated via the API.
     */
    public function update(User $user, User $author): bool
    {
        return false;
    }

    /**
     * Authors cannot be deleted via the API.
     */
    public function delete(User $user, User $author): bool
    {
        return false;
    }

    /**
     * Authors cannot be restored via the API.
     */
    public function restore(User $user, User $author): bool
    {
        return false;
    }

    /**
     * Authors cannot be permanently deleted via the API.
     */
    public function forceDelete(User $user, User $author): bool
    {
        return false;
    }
}
