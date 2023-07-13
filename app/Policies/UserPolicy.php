<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        if ($user->isRegularUser()) {
            return false;
        }

        return true;
    }

    public function store(User $user): bool
    {
        if ($user->isRegularUser()) {
            return false;
        }

        return true;
    }

    public function view(User $loggedInUser, User $user): bool
    {
        if ($loggedInUser->isRegularUser() && $loggedInUser->id !== $user->id) {
            return false;
        }

        return true;
    }

    public function update(User $loggedInUser, User $user): bool
    {
        if ($loggedInUser->isRegularUser() && $loggedInUser->id !== $user->id) {
            return false;
        }

        return true;
    }
}
