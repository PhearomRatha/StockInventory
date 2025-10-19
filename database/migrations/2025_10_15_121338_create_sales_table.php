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
       Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('customers');
    $table->string('invoice_number')->unique();
    $table->decimal('total_amount', 10, 2);
    $table->decimal('discount', 10, 2)->default(0);
    $table->enum('payment_status', ['paid','unpaid','partial'])->default('unpaid');
    $table->string('payment_method')->nullable();
    $table->foreignId('sold_by')->constrained('users');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
