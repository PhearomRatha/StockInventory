<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PERFORMANCE OPTIMIZATION: Add critical indexes for frequently queried columns
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('status'); // For WHERE status = 'active'
            $table->index('role_id'); // For role relationships
            $table->index('created_at'); // For date-based queries
            $table->index(['email']); // For login queries
        });

        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id'); // For category relationships
            $table->index('supplier_id'); // For supplier relationships
            $table->index('stock_quantity'); // For stock status queries
            $table->index('is_low_stock'); // For low stock filtering
            $table->index('sku'); // For SKU lookups
            $table->index('barcode'); // For barcode lookups
            $table->index('created_at'); // For date-based queries
            // Composite index for stock status queries
            $table->index(['stock_quantity', 'is_low_stock']);
        });

        // Sales table indexes
        Schema::table('sales', function (Blueprint $table) {
            $table->index('customer_id'); // For customer relationships
            $table->index('sold_by'); // For user relationships
            $table->index('status'); // For status filtering
            $table->index('created_at'); // For date-based queries (CRITICAL for reports)
            // Composite index for monthly reports
            $table->index(['created_at', 'total_amount']);
        });

        // Stock_ins table indexes
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->index('product_id'); // For product relationships
            $table->index('supplier_id'); // For supplier relationships
            $table->index('received_by'); // For user relationships
            $table->index('created_at'); // For date-based queries (CRITICAL)
            // Composite index for supplier reports
            $table->index(['supplier_id', 'created_at']);
        });

        // Stock_outs table indexes
        Schema::table('stock_outs', function (Blueprint $table) {
            $table->index('product_id'); // For product relationships
            $table->index('customer_id'); // For customer relationships
            $table->index('sold_by'); // For user relationships
            $table->index('created_at'); // For date-based queries (CRITICAL)
            // Composite index for product sales reports
            $table->index(['product_id', 'created_at']);
        });

        // Customers table indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->index('email'); // For email lookups
            $table->index('phone'); // For phone lookups
            $table->index('type'); // For customer type filtering
            $table->index('created_at'); // For date-based queries
        });

        // Suppliers table indexes
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('email'); // For email lookups
            $table->index('phone'); // For phone lookups
            $table->index('created_at'); // For date-based queries
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('reference_id'); // For polymorphic relationships
            $table->index('reference_type'); // For polymorphic relationships
            $table->index('recorded_by'); // For user relationships
            $table->index('status'); // For status filtering
            $table->index('created_at'); // For date-based queries
            // Composite index for polymorphic queries
            $table->index(['reference_id', 'reference_type']);
        });

        // Activity_logs table indexes (using action and module instead of type)
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('user_id'); // For user relationships
            $table->index('action'); // For action filtering
            $table->index('module'); // For module filtering
            $table->index('created_at'); // For date-based queries (CRITICAL)
            // Composite index for log reports
            $table->index(['action', 'created_at']);
        });

        // Categories table indexes
        Schema::table('categories', function (Blueprint $table) {
            $table->index('name'); // For name lookups
        });

        // User_requests table indexes (uses email, verification_status)
        Schema::table('user_requests', function (Blueprint $table) {
            $table->index('email'); // For email lookups
            $table->index('verification_status'); // For status filtering
            $table->index('created_at'); // For date-based queries
            // Composite index for pending requests
            $table->index(['verification_status', 'created_at']);
        });

        // Sale_items table indexes
        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('sale_id'); // For sale relationships
            $table->index('product_id'); // For product relationships
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status', 'role_id', 'created_at', 'email']);
        });

        // Products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'supplier_id', 'stock_quantity', 'is_low_stock', 'sku', 'barcode', 'created_at']);
            $table->dropIndex(['products_stock_quantity_is_low_stock_index']);
        });

        // Sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'sold_by', 'status', 'created_at']);
            $table->dropIndex(['sales_created_at_total_amount_index']);
        });

        // Stock_ins table
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'supplier_id', 'received_by', 'created_at']);
            $table->dropIndex(['stock_ins_supplier_id_created_at_index']);
        });

        // Stock_outs table
        Schema::table('stock_outs', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'customer_id', 'sold_by', 'created_at']);
            $table->dropIndex(['stock_outs_product_id_created_at_index']);
        });

        // Customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['email', 'phone', 'type', 'created_at']);
        });

        // Suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['email', 'phone', 'created_at']);
        });

        // Payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['reference_id', 'reference_type', 'recorded_by', 'status', 'created_at']);
        });

        // Activity_logs table
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'action', 'module', 'created_at']);
            $table->dropIndex(['activity_logs_action_created_at_index']);
        });

        // Categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        // User_requests table
        Schema::table('user_requests', function (Blueprint $table) {
            $table->dropIndex(['email', 'verification_status', 'created_at']);
            $table->dropIndex(['user_requests_verification_status_created_at_index']);
        });

        // Sale_items table
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex(['sale_id', 'product_id']);
        });
    }
};
