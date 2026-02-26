<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add Google ID for OAuth users
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('password');
            }

            // Add created_by field to track who created the user
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('role_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['google_id', 'created_by']);
        });
    }
};
