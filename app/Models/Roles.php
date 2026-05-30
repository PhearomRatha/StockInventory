<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    public const ROLE_ADMIN = 'Admin';
    public const ROLE_MANAGER = 'Manager';
    public const ROLE_STAFF = 'Staff';
    public const ROLE_CASHER = 'Casher';

    protected $fillable = [
        'name',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->name === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->name === self::ROLE_MANAGER;
    }

    public function isStaff(): bool
    {
        return $this->name === self::ROLE_STAFF;
    }

    public function isCasher(): bool
    {
        return $this->name === self::ROLE_CASHER;
    }

    public static function getRoleNames(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_STAFF,
            self::ROLE_CASHER,
        ];
    }

    public static function getIdByName(string $name): ?int
    {
        $role = self::where('name', $name)->first();
        return $role ? $role->id : null;
    }
}