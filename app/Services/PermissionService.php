<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    /**
     * Check if user has specific permission (format: "module.action" e.g. "products.create")
     * Dynamically checks via role -> permissions pivot table (RBAC)
     */
    public static function can(User $user, string $ability): bool
    {
        if (! $user->role) {
            return false;
        }

        if ($user->isInactive()) {
            return false;
        }

        // Support both "module.action" and legacy "action_module" but prefer module.action
        $parts = explode('.', $ability);
        if (count($parts) === 2) {
            [$module, $action] = $parts;
            return $user->role->permissions()
                ->where('module', $module)
                ->where('action', $action)
                ->exists();
        }

        // Fallback for old style like create_product -> products.create ? but we standardize on module.action
        return false;
    }

    /**
     * Alias for can() for backward compat
     */
    public static function hasPermission(User $user, string $permission): bool
    {
        return self::can($user, $permission);
    }

    /**
     * Get all permissions for a user as array of "module.action" strings
     */
    public static function getPermissions(User $user): array
    {
        if (! $user->role) {
            return [];
        }

        return $user->role->permissions()
            ->get(['module', 'action'])
            ->map(fn ($p) => "{$p->module}.{$p->action}")
            ->toArray();
    }
}
