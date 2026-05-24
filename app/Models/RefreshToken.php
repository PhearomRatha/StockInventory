<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    use HasFactory;

    protected $table = 'refresh_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if token is revoked
     */
    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    /**
     * Check if token has expired
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if token is valid (not revoked and not expired)
     */
    public function isValid(): bool
    {
        return !$this->isRevoked() && !$this->isExpired();
    }

    /**
     * Revoke this refresh token
     */
    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    /**
     * Scope for active (valid) tokens
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public static function revokeAllUserTokens(int $userId): int
    {
        return static::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * Revoke all refresh tokens for a user except the current one
     */
    public static function revokeAllExcept(int $userId, string $currentTokenId): int
    {
        return static::where('user_id', $userId)
            ->where('id', '!=', $currentTokenId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * Cleanup expired refresh tokens
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now())
            ->orWhereNotNull('revoked_at')
            ->delete();
    }
}