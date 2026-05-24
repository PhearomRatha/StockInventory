<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('products', 'category_id', 'idx_products_category');
        $this->addIndex('products', 'supplier_id', 'idx_products_supplier');

        $this->addIndex('stock_transactions', 'product_id', 'idx_stock_product');
        $this->addIndex('stock_transactions', 'warehouse_id', 'idx_stock_warehouse');
        $this->addIndex('stock_transactions', 'type', 'idx_stock_type');
        $this->addIndex('stock_transactions', 'created_at', 'idx_stock_created');

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'warehouse_id')) {
            $this->addIndex('sales', 'warehouse_id', 'idx_sales_warehouse');
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'sold_at')) {
            $this->addIndex('sales', 'sold_at', 'idx_sales_date');
        }

        $this->addIndex('sale_items', 'sale_id', 'idx_sale_items_sale');
        $this->addIndex('activity_logs', 'user_id', 'idx_logs_user');

        if (Schema::hasTable('warehouse_products')) {
            $this->addIndex('warehouse_products', ['warehouse_id', 'product_id'], 'idx_warehouse_products_lookup');
        }
    }

    public function down(): void
    {
        $this->dropIndex('products', 'idx_products_category');
        $this->dropIndex('products', 'idx_products_supplier');
        $this->dropIndex('stock_transactions', 'idx_stock_product');
        $this->dropIndex('stock_transactions', 'idx_stock_warehouse');
        $this->dropIndex('stock_transactions', 'idx_stock_type');
        $this->dropIndex('stock_transactions', 'idx_stock_created');
        $this->dropIndex('sales', 'idx_sales_warehouse');
        $this->dropIndex('sales', 'idx_sales_date');
        $this->dropIndex('sale_items', 'idx_sale_items_sale');
        $this->dropIndex('activity_logs', 'idx_logs_user');
        $this->dropIndex('warehouse_products', 'idx_warehouse_products_lookup');
    }

    private function addIndex(string $table, string|array $columns, string $name): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (!Schema::hasTable($table) || !Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropIndex($name);
        });
    }
};
