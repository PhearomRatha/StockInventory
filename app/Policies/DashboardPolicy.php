<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class DashboardPolicy
{
    /**
     * Determine whether the user can view the dashboard.
     */
    public function view(User $user): bool
    {
        return $user->can('dashboard.view');
    }
}