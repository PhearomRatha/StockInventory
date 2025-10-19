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
       Schema::create('stock_outs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('customers');
    $table->foreignId('product_id')->constrained('products');
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('total_amount', 10, 2);
    $table->date('sold_date');
    $table->foreignId('sold_by')->constrained('users');
    $table->text('remarks')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_outs');
    }
};
