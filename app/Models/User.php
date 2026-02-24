<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role_id',
        'google_id',
        'email_verified_at',
    ];

    /**
     * Role constants
     */
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_MANAGER = 'Manager';
    public const ROLE_STAFF = 'Staff';

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Role relationship
     */
    public function role() {
        return $this->belongsTo(Roles::class);
    }

    /**
     * Stock ins relationship
     */
    public function stockIns() {
        return $this->hasMany(Stock_ins::class, 'received_by');
    }

    /**
     * Stock outs relationship
     */
    public function stockOuts() {
        return $this->hasMany(Stock_outs::class, 'sold_by');
    }

    /**
     * Sales relationship
     */
    public function sales() {
        return $this->hasMany(Sales::class, 'sold_by');
    }

    /**
     * Payments relationship
     */
    public function payments() {
        return $this->hasMany(Payments::class, 'recorded_by');
    }

    /**
     * Activity logs relationship
     */
    public function activityLogs() {
        return $this->hasMany(Activity_logs::class);
    }

    /**
     * Check if user is Admin
     */
    public function isAdmin(): bool
    {
        return $this->role && $this->role->name === self::ROLE_ADMIN;
    }

    /**
     * Check if user is Manager
     */
    public function isManager(): bool
    {
        return $this->role && $this->role->name === self::ROLE_MANAGER;
    }

    /**
     * Check if user is Staff
     */
    public function isStaff(): bool
    {
        return $this->role && $this->role->name === self::ROLE_STAFF;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasRole(array $roles): bool
    {
        return $this->role && in_array($this->role->name, $roles);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user has password set
     */
    public function hasPassword(): bool
    {
        return !empty($this->password);
    }

    /**
     * Check if user logged in via Google
     */
    public function isGoogleUser(): bool
    {
        return !empty($this->google_id);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * be cast.
     The attributes that should *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the admin who created this user
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Users created by this admin
     */
    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }
}
