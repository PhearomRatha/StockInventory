<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    /**
     * Determine whether the user can view a role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    /**
     * Determine whether the user can update a role.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.update');
    }

    /**
     * Determine whether the user can delete a role.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete');
    }
}