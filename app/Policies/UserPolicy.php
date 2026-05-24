<?php

namespace App\Policies;

use App\Models\User;
use App\Models\User as TargetUser;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, TargetUser $targetUser): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can update a user.
     */
    public function update(User $user, TargetUser $targetUser): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete a user.
     */
    public function delete(User $user, TargetUser $targetUser): bool
    {
        return $user->isAdmin();
    }
}