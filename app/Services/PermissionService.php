<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    /**
     * Check if user has specific permission
     */
    public static function hasPermission(User $user, string $permission): bool
    {
        return match ($permission) {
            'create_product', 'edit_product', 'delete_product' => in_array($user->role->name ?? '', [User::ROLE_ADMIN, User::ROLE_MANAGER]),
            'view_reports' => in_array($user->role->name ?? '', [User::ROLE_ADMIN, User::ROLE_MANAGER]),
            'manage_users' => $user->role->name === User::ROLE_ADMIN,
            'manage_roles' => $user->role->name === User::ROLE_ADMIN,
            default => false,
        };
    }

    /**
     * Check if user can perform action
     */
    public static function can(User $user, string $ability): bool
    {
        $rolePermissions = [
            User::ROLE_ADMIN => [
                'create_product', 'edit_product', 'delete_product',
                'view_reports', 'manage_users', 'manage_roles',
                'create_category', 'edit_category', 'delete_category',
                'create_supplier', 'edit_supplier', 'delete_supplier',
                'create_sale', 'view_sale',
                'create_stock_in', 'view_stock_in',
            ],
            User::ROLE_MANAGER => [
                'create_product', 'edit_product', 'delete_product',
                'view_reports',
                'create_category', 'edit_category',
                'create_supplier', 'edit_supplier',
                'create_sale', 'view_sale',
                'create_stock_in', 'view_stock_in',
            ],
            User::ROLE_STAFF => [
                'view_product',
                'create_sale', 'view_sale',
                'view_stock_in',
            ],
        ];

        $role = $user->role->name ?? User::ROLE_STAFF;

        return in_array($ability, $rolePermissions[$role] ?? []);
    }

    /**
     * Get all permissions for a user
     */
    public static function getPermissions(User $user): array
    {
        $rolePermissions = [
            User::ROLE_ADMIN => [
                'create_product', 'edit_product', 'delete_product',
                'view_reports', 'manage_users', 'manage_roles',
                'create_category', 'edit_category', 'delete_category',
                'create_supplier', 'edit_supplier', 'delete_supplier',
                'create_sale', 'view_sale',
                'create_stock_in', 'view_stock_in',
            ],
            User::ROLE_MANAGER => [
                'create_product', 'edit_product', 'delete_product',
                'view_reports',
                'create_category', 'edit_category',
                'create_supplier', 'edit_supplier',
                'create_sale', 'view_sale',
                'create_stock_in', 'view_stock_in',
            ],
            User::ROLE_STAFF => [
                'view_product',
                'create_sale', 'view_sale',
                'view_stock_in',
            ],
        ];

        $role = $user->role->name ?? User::ROLE_STAFF;

        return $rolePermissions[$role] ?? [];
    }
}
