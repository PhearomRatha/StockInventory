<?php

namespace App\Policies;

use App\Models\StockTransaction;
use App\Models\User;

class StockTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, StockTransaction $transaction): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.create');
    }
}
