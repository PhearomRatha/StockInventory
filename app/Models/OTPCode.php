<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTPCode extends Model
{
    use HasFactory;

    protected $table = 'otp_codes';

    protected $fillable = [
        'otp_type',
        'email',
        'code',
        'expires_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Check if OTP has expired
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if max attempts reached (default limit: 5)
     */
    public function isMaxAttemptsReached(int $maxAttempts = 5): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    /**
     * Verify the OTP code
     */
    public function verifyCode(string $code): bool
    {
        return $this->code === $code && !$this->isExpired();
    }

    /**
     * Increment attempts
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Scope for active (non-expired) OTPs
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope by type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('otp_type', $type);
    }

    /**
     * Cleanup expired OTP codes
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}