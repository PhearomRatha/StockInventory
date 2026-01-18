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
        DB::statement("SELECT setval('categories_id_seq', (SELECT COALESCE(MAX(id), 0) FROM categories))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse sequence reset
    }
};
