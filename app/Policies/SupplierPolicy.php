<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Suppliers;
use Illuminate\Auth\Access\Response;

class SupplierPolicy
{
    /**
     * Determine whether the user can view any suppliers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('suppliers.view');
    }

    /**
     * Determine whether the user can view a supplier.
     */
    public function view(User $user, Suppliers $supplier): bool
    {
        return $user->can('suppliers.view');
    }

    /**
     * Determine whether the user can create suppliers.
     */
    public function create(User $user): bool
    {
        return $user->can('suppliers.create');
    }

    /**
     * Determine whether the user can update a supplier.
     */
    public function update(User $user, Suppliers $supplier): bool
    {
        return $user->can('suppliers.update');
    }

    /**
     * Determine whether the user can delete a supplier.
     */
    public function delete(User $user, Suppliers $supplier): bool
    {
        return $user->can('suppliers.delete');
    }
}