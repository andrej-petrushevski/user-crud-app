<?php

namespace App\Contracts;

use App\Models\User;

interface UserServiceInterface
{
    public function createUser(User $user): void;
}
