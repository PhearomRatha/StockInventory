<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Categories;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('categories.view');
    }

    /**
     * Determine whether the user can view a category.
     */
    public function view(User $user, Categories $category): bool
    {
        return $user->can('categories.view');
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    /**
     * Determine whether the user can update a category.
     */
    public function update(User $user, Categories $category): bool
    {
        return $user->can('categories.update');
    }

    /**
     * Determine whether the user can delete a category.
     */
    public function delete(User $user, Categories $category): bool
    {
        return $user->can('categories.delete');
    }
}