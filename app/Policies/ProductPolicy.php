<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Products;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can view a product.
     */
    public function view(User $user, Products $product): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    /**
     * Determine whether the user can update a product.
     */
    public function update(User $user, Products $product): bool
    {
        return $user->can('products.update');
    }

    /**
     * Determine whether the user can delete a product.
     */
    public function delete(User $user, Products $product): bool
    {
        return $user->can('products.delete');
    }
}