<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Activity_logs;
use Illuminate\Auth\Access\Response;

class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any activity logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('activity-logs.view');
    }

    /**
     * Determine whether the user can view an activity log.
     */
    public function view(User $user, Activity_logs $activityLog): bool
    {
        return $user->can('activity-logs.view');
    }

    /**
     * Determine whether the user can create activity logs.
     */
    public function create(User $user): bool
    {
        return $user->can('activity-logs.create');
    }

    /**
     * Determine whether the user can update an activity log.
     */
    public function update(User $user, Activity_logs $activityLog): bool
    {
        return $user->can('activity-logs.update');
    }

    /**
     * Determine whether the user can delete an activity log.
     */
    public function delete(User $user, Activity_logs $activityLog): bool
    {
        return $user->can('activity-logs.delete');
    }
}