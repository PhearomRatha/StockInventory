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
        Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->morphs('reference'); // reference_type & reference_id
    $table->decimal('amount', 10, 2);
    $table->enum('payment_type', ['income','expense']);
    $table->string('payment_method');
    $table->string('paid_to_from');
    $table->date('payment_date');
    $table->foreignId('recorded_by')->constrained('users');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
