<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class WarehousePolicy
{
    /**
     * Determine whether the user can view any warehouses.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('warehouses.view');
    }

    /**
     * Determine whether the user can view a warehouse.
     */
    public function view(User $user, \App\Models\Warehouse $warehouse): bool
    {
        return $user->can('warehouses.view');
    }

    /**
     * Determine whether the user can create warehouses.
     */
    public function create(User $user): bool
    {
        return $user->can('warehouses.create');
    }

    /**
     * Determine whether the user can update a warehouse.
     */
    public function update(User $user, \App\Models\Warehouse $warehouse): bool
    {
        return $user->can('warehouses.update');
    }

    /**
     * Determine whether the user can delete a warehouse.
     */
    public function delete(User $user, \App\Models\Warehouse $warehouse): bool
    {
        return $user->can('warehouses.delete');
    }
}