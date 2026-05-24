<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payments;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can view a payment.
     */
    public function view(User $user, Payments $payment): bool
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    /**
     * Determine whether the user can update a payment.
     */
    public function update(User $user, Payments $payment): bool
    {
        return $user->can('payments.update');
    }

    /**
     * Determine whether the user can delete a payment.
     */
    public function delete(User $user, Payments $payment): bool
    {
        return $user->can('payments.delete');
    }
}