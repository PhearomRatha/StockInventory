<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    /**
     * Permission constants for module-based access control.
     * Each module supports: view, create, update, delete, approve, export, print
     */
    public const MODULES = [
        'dashboard',
        'products',
        'inventory',
        'categories',
        'suppliers',
        'customers',
        'sales',
        'payments',
        'reports',
        'users',
        'roles',
        'settings',
        'activity-logs',
        'warehouses',
    ];

    public const ACTIONS = [
        'view',
        'create',
        'update',
        'delete',
        'approve',
        'export',
        'print',
    ];

    protected $fillable = [
        'module',
        'action',
        'description',
    ];

    /**
     * Roles that have this permission
     */
    public function roles()
    {
        return $this->belongsToMany(Roles::class, 'role_permissions', 'permission_id', 'role_id');
    }
}