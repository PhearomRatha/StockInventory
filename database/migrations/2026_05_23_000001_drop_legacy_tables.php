<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop all legacy tables that are NOT part of the final target schema.
     * Target schema is the single source of truth.
     * Keeps only framework-required tables + listed business tables.
     */
    public function up(): void
    {
        // Legacy stock tables (replaced by stock_transactions, adjustments, transfers)
        Schema::dropIfExists('stock_ins');
        Schema::dropIfExists('stock_outs');

        // Note: user_requests kept temporarily as AdminController still references old approval flow (can be removed after full RBAC migration)

        // Legacy auth/token tables not in final schema (Sanctum still needs personal_access_tokens)
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('refresh_tokens');
        // Note: token_revocations is still in use by TokenService, do not drop

        // Old password reset (final uses password_reset_tokens which exists)
        // Note: we keep personal_access_tokens for Sanctum, failed_jobs for queue if used

        // Any other non-listed? e.g. if any old ones
    }

    public function down(): void
    {
        // No easy restore; down would require re-creating legacy which we don't want
    }
};
