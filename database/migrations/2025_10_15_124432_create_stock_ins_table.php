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
       Schema::create('stock_ins', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers');
    $table->foreignId('product_id')->constrained('products');
    $table->integer('quantity');
    $table->decimal('unit_cost', 10, 2);
    $table->decimal('total_cost', 10, 2);
    $table->date('received_date');
    $table->foreignId('received_by')->constrained('users');
    $table->text('remarks')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};
