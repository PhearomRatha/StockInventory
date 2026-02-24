<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    /**
     * Role constants
     */
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_MANAGER = 'Manager';
    public const ROLE_STAFF = 'Staff';

    protected $fillable = [
        'name',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Users relationship
     */
    public function users() {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this role is Admin
     */
    public function isAdmin(): bool
    {
        return $this->name === self::ROLE_ADMIN;
    }

    /**
     * Check if this role is Manager
     */
    public function isManager(): bool
    {
        return $this->name === self::ROLE_MANAGER;
    }

    /**
     * Check if this role is Staff
     */
    public function isStaff(): bool
    {
        return $this->name === self::ROLE_STAFF;
    }

    /**
     * Get all available roles as array
     */
    public static function getRoleNames(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_STAFF,
        ];
    }

    /**
     * Get role ID by name
     */
    public static function getIdByName(string $name): ?int
    {
        $role = self::where('name', $name)->first();
        return $role ? $role->id : null;
    }
}
