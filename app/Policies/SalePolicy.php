<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sales;
use Illuminate\Auth\Access\Response;

class SalePolicy
{
    /**
     * Determine whether the user can view any sales.
     */
public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Sales $sale): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can view a sale.
     */
    public function view(User $user, Sales $sale): bool
    {
        return $user->can('sales.view');
    }

    /**
     * Determine whether the user can create sales.
     */
    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    /**
     * Determine whether the user can update a sale.
     */
    public function update(User $user, Sales $sale): bool
    {
        return $user->can('sales.update');
    }

    /**
     * Determine whether the user can delete a sale.
     */
    public function delete(User $user, Sales $sale): bool
    {
        return $user->can('sales.delete');
    }
}