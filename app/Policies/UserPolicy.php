<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function boss(User $user): bool
    {
        return $user->isBoss();
    }

    public function operator(User $user): bool
    {
        return $user->isOperator();
    }
}
