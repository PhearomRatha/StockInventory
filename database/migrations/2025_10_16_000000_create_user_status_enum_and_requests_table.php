<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the user_status ENUM type and user_requests table for pending registrations
     */
    public function up(): void
    {
        // Drop existing ENUM type if it exists (for fresh migrations)
        DB::statement("DROP TYPE IF EXISTS user_status CASCADE");

        // Create ENUM type for user status
        DB::statement("CREATE TYPE user_status AS ENUM ('PENDING', 'INACTIVE', 'ACTIVE')");

        // Update users table to use ENUM type
        Schema::table('users', function (Blueprint $table) {
            // Drop the existing status column and recreate with ENUM
            $table->dropColumn('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['PENDING', 'INACTIVE', 'ACTIVE'])->default('PENDING')->after('password');
        });

        // Create user_requests table for pending registrations
        Schema::create('user_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('otp_hash'); // Hashed OTP
            $table->timestamp('otp_expires_at');
            $table->integer('otp_attempts')->default(0); // Track OTP verification attempts
            $table->string('role_id')->default(2); // Default to 'User' role
            $table->enum('verification_status', ['PENDING', 'VERIFIED', 'EXPIRED'])->default('PENDING');
            $table->timestamps();
        });

        // Create token_revocation table for JWT token revocation
        Schema::create('token_revocation', function (Blueprint $table) {
            $table->id();
            $table->string('token_id')->unique(); // JWT ID (jti claim)
            $table->unsignedBigInteger('user_id');
            $table->timestamp('expires_at'); // When the token naturally expires
            $table->timestamp('revoked_at')->nullable(); // When it was manually revoked
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['token_id']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_revocation');
        Schema::dropIfExists('user_requests');

        // Revert users table to boolean status
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('status')->default(true);
        });

        // Drop the ENUM type
        DB::statement("DROP TYPE IF EXISTS user_status CASCADE");
    }
};
