<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReportPolicy
{
    /**
     * Determine whether the user can view sales reports.
     */
    public function viewSales(User $user): bool
    {
        return $user->can('reports.sales');
    }

    /**
     * Determine whether the user can view financial reports.
     */
    public function viewFinancial(User $user): bool
    {
        return $user->can('reports.financial');
    }

    /**
     * Determine whether the user can view stock reports.
     */
    public function viewStock(User $user): bool
    {
        return $user->can('reports.stock');
    }

    /**
     * Determine whether the user can view activity log reports.
     */
    public function viewActivityLogs(User $user): bool
    {
        return $user->can('reports.activity-logs');
    }

    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('reports.view');
    }
}