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
        if (!$user->role_id) {
            return false;
        }

        if ($user->isInactive()) {
            return false;
        }

        // Force reload role with permissions if not loaded
        if (!$user->relationLoaded('role') || !$user->role->relationLoaded('permissions')) {
            $user->load('role.permissions');
        }

        if (!$user->role) {
            return false;
        }

        $parts = explode('.', $ability);
        if (count($parts) === 2) {
            [$module, $action] = $parts;

            // Check from already-loaded collection, no extra DB query
            return $user->role->permissions
                ->where('module', $module)
                ->where('action', $action)
                ->isNotEmpty();
        }

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
            ->map(fn($p) => "{$p->module}.{$p->action}")
            ->toArray();
    }
}
