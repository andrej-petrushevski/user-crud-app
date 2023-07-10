<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function index(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    public function store(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }
}
