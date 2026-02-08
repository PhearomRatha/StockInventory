<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'otp_hash',
        'otp_expires_at',
        'otp_attempts',
        'role_id',
        'verification_status',
    ];

    protected $hidden = [
        'otp_hash',
        'password',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'otp_attempts' => 'integer',
    ];

    /**
     * Get the role associated with this request
     */
    public function role()
    {
        return $this->belongsTo(Roles::class);
    }

    /**
     * Check if OTP has expired
     */
    public function isOtpExpired(): bool
    {
        return now()->isAfter($this->otp_expires_at);
    }

    /**
     * Increment OTP attempts
     */
    public function incrementOtpAttempts(): void
    {
        $this->increment('otp_attempts');
    }

    /**
     * Reset OTP attempts
     */
    public function resetOtpAttempts(): void
    {
        $this->otp_attempts = 0;
        $this->save();
    }

    /**
     * Check if maximum OTP attempts reached (max 5 attempts)
     */
    public function isMaxOtpAttemptsReached(): bool
    {
        return $this->otp_attempts >= 5;
    }
}
