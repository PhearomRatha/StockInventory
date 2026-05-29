<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'password',
        'role_id',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'otp_attempts' => 'integer',
        'role_id' => 'integer',
    ];

    /**
     * Get the user associated with this request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role associated with this request
     */
    public function role()
    {
        return $this->belongsTo(Roles::class);
    }
}
