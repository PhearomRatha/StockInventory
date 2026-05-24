<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customers;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    /**
     * Determine whether the user can view a customer.
     */
    public function view(User $user, Customers $customer): bool
    {
        return $user->can('customers.view');
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    /**
     * Determine whether the user can update a customer.
     */
    public function update(User $user, Customers $customer): bool
    {
        return $user->can('customers.update');
    }

    /**
     * Determine whether the user can delete a customer.
     */
    public function delete(User $user, Customers $customer): bool
    {
        return $user->can('customers.delete');
    }
}