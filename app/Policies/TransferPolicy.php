<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.create');
    }

    public function approve(User $user, Transfer $transfer): bool
    {
        return $user->can('inventory.approve');
    }

    public function complete(User $user, Transfer $transfer): bool
    {
        return $user->can('inventory.approve') || $user->can('inventory.update');
    }

    public function reject(User $user, Transfer $transfer): bool
    {
        return $user->can('inventory.approve');
    }
}
