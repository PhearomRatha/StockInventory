<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support change() without doctrine/dbal
        // For PostgreSQL, the original change() works
        if (DB::getDriverName() === 'pgsql') {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('customer_id')->change();
            });
        }
    }
};
