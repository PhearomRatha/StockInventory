<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenRevocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_id',
        'user_id',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the user associated with this revoked token
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
     * Revoke this token
     */
    public function revoke(): void
    {
        $this->revoked_at = now();
        $this->save();
    }

    /**
     * Check if token has naturally expired
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Scope for active (non-revoked) tokens
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Scope for expired tokens
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Check if a token is valid (not revoked and not expired)
     */
    public static function isTokenValid(string $tokenId): bool
    {
        $revocation = static::where('token_id', $tokenId)->first();
        
        if (!$revocation) {
            return false;
        }

        return !$revocation->isRevoked() && !$revocation->isExpired();
    }

    /**
     * Revoke a token by its ID
     */
    public static function revokeToken(string $tokenId): bool
    {
        $revocation = static::where('token_id', $tokenId)->first();
        
        if ($revocation) {
            $revocation->revoke();
            return true;
        }

        return false;
    }

    /**
     * Revoke all tokens for a specific user
     */
    public static function revokeAllUserTokens(int $userId): int
    {
        return static::where('user_id', $userId)
            ->active()
            ->update(['revoked_at' => now()]);
    }

    /**
     * Clean up expired tokens (garbage collection)
     */
    public static function cleanupExpiredTokens(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}
