<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align existing tables with the final database design.
     */
    public function up(): void
    {
        $this->dropLegacyStockTables();
        $this->alignUsers();
        $this->alignRoles();
        $this->alignPermissions();
        $this->dropUserRoles();
        $this->alignActivityLogs();
        $this->alignSuppliers();
        $this->alignCustomers();
        $this->alignWarehouses();
        $this->createWarehouseProducts();
        $this->migrateProductStockToWarehouses();
        $this->alignProducts();
        $this->alignSales();
        $this->alignSaleItems();
        $this->alignPayments();
        $this->alignStockTransactions();
        $this->createStockAdjustments();
        $this->createTransfers();
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('warehouse_products');
    }

    private function dropLegacyStockTables(): void
    {
        Schema::dropIfExists('stock_ins');
        Schema::dropIfExists('stock_outs');
    }

    private function dropUserRoles(): void
    {
        Schema::dropIfExists('user_roles');
    }

    private function dropColumnsIfExist(string $table, array $columns): void
    {
        // SQLite has issues dropping columns with indexes, skip for SQLite
        if (DB::getDriverName() === 'pgsql') {
            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }
    }

    private function alignUsers(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        // SQLite: Skip column drops that would conflict with existing indexes
        if (DB::getDriverName() !== 'pgsql') {
            // For SQLite, just ensure the correct columns exist
            if (!Schema::hasColumn('users', 'status')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->after('password');
                });
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->timestamp('last_login_at')->nullable()->after('status');
                });
            }
            return;
        }

        $this->dropColumnsIfExist('users', [
            'email_token',
            'remember_token',
            'email_verified_at',
            'verification_token',
            'verification_expires_at',
            'otp_code',
            'otp_expires_at',
            'otp_hash',
            'otp_attempts',
            'verification_status',
            'google_id',
            'created_by',
        ]);

        if (Schema::hasColumn('users', 'status')) {
            DB::table('users')->where('status', 'PENDING')->update(['status' => 'INACTIVE']);
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->after('password');
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('status');
            }
        });

        if (Schema::hasColumn('users', 'role_id')) {
            DB::table('users')->whereNull('role_id')->update(['role_id' => 2]);

            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->nullable(false)->change();
            });
        }
    }

    private function alignRoles(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $this->dropColumnsIfExist('roles', ['permissions']);

        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique('name');
            });
        } catch (\Throwable) {
            // Index may already exist.
        }
    }

    private function alignPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        // SQLite: skip column changes without doctrine/dbal
        if (DB::getDriverName() !== 'pgsql') {
            if (!Schema::hasColumn('permissions', 'description')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->text('description')->nullable()->after('name');
                });
            }
            return;
        }

        if (Schema::hasColumn('permissions', 'description')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            });
        }
    }

    private function alignActivityLogs(): void
    {
        if (!Schema::hasTable('activity_logs')) {
            return;
        }

        $this->dropColumnsIfExist('activity_logs', ['record_id', 'updated_at']);

        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_logs', 'description')) {
                $table->text('description')->after('action');
            }
            if (!Schema::hasColumn('activity_logs', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('description');
            }
            if (!Schema::hasColumn('activity_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });

        if (Schema::hasColumn('activity_logs', 'description')) {
            DB::table('activity_logs')
                ->whereNull('description')
                ->update(['description' => '']);
        }
    }

    private function alignSuppliers(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        $this->dropColumnsIfExist('suppliers', ['contact']);

        if (!Schema::hasColumn('suppliers', 'status')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->boolean('status')->default(true)->after('address');
            });
        }
    }

    private function alignCustomers(): void
    {
        $this->dropColumnsIfExist('customers', ['preferences']);
    }

    private function alignWarehouses(): void
    {
        if (!Schema::hasTable('warehouses')) {
            return;
        }

        $this->dropColumnsIfExist('warehouses', [
            'address',
            'city',
            'state',
            'zip_code',
            'country',
            'phone',
            'email',
            'notes',
            'deleted_at',
        ]);

        if (Schema::hasColumn('warehouses', 'is_active') && !Schema::hasColumn('warehouses', 'status')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE warehouses RENAME COLUMN is_active TO status');
            } else {
                Schema::table('warehouses', function (Blueprint $table) {
                    $table->renameColumn('is_active', 'status');
                });
            }
        } elseif (!Schema::hasColumn('warehouses', 'status')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->boolean('status')->default(true)->after('location');
            });
        }

        if (Schema::hasColumn('warehouses', 'created_by')) {
            // SQLite: skip column change without doctrine/dbal
            if (DB::getDriverName() === 'pgsql') {
                Schema::table('warehouses', function (Blueprint $table) {
                    $table->unsignedBigInteger('created_by')->nullable(false)->change();
                });
            }
        }
    }

    private function alignProducts(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        $this->dropColumnsIfExist('products', ['stock_quantity', 'is_low_stock']);

        // SQLite: skip column changes without doctrine/dbal
        if (DB::getDriverName() !== 'pgsql') {
            if (!Schema::hasColumn('products', 'status')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->boolean('status')->default(true)->after('reorder_level');
                });
            }
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->change();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();

            if (Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->change();
            }

            if (!Schema::hasColumn('products', 'status')) {
                $table->boolean('status')->default(true)->after('reorder_level');
            }
        });

        foreach (['cost', 'price'] as $column) {
            if (Schema::hasColumn('products', $column)) {
                Schema::table('products', function (Blueprint $table) use ($column) {
                    $table->decimal($column, 15, 2)->change();
                });
            }
        }
    }

    private function createWarehouseProducts(): void
    {
        if (Schema::hasTable('warehouse_products')) {
            return;
        }

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['warehouse_id', 'product_id']);
        });
    }

    private function migrateProductStockToWarehouses(): void
    {
        if (!Schema::hasTable('warehouse_products')) {
            return;
        }

        $warehouseId = DB::table('warehouses')->orderBy('id')->value('id');

        if (!$warehouseId) {
            $adminId = DB::table('users')->orderBy('id')->value('id');

            if (!$adminId) {
                return;
            }

            $warehouseId = DB::table('warehouses')->insertGetId([
                'name' => 'Main Warehouse',
                'code' => 'MAIN',
                'location' => null,
                'status' => true,
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!Schema::hasColumn('products', 'stock_quantity')) {
            return;
        }

        $products = DB::table('products')
            ->select('id', 'stock_quantity')
            ->where('stock_quantity', '>', 0)
            ->get();

        foreach ($products as $product) {
            DB::table('warehouse_products')->updateOrInsert(
                [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $product->stock_quantity,
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function alignSales(): void
    {
        if (!Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('customer_id')->constrained('warehouses');
            }
            if (!Schema::hasColumn('sales', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('invoice_number');
            }
            if (!Schema::hasColumn('sales', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('sales', 'notes')) {
                $table->text('notes')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('sales', 'sold_at')) {
                $table->timestamp('sold_at')->nullable()->after('sold_by');
            }
        });

        if (Schema::hasColumn('sales', 'total_amount') && !Schema::hasColumn('sales', 'total')) {
            if (DB::getDriverName() !== 'pgsql') {
                // SQLite doesn't support renameColumn easily
                // Skip for now
            } else {
                Schema::table('sales', function (Blueprint $table) {
                    $table->renameColumn('total_amount', 'total');
                });
            }
        }

        // SQLite: skip column changes without doctrine/dbal
        if (DB::getDriverName() === 'pgsql') {
            foreach (['subtotal', 'discount', 'tax', 'total'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    Schema::table('sales', function (Blueprint $table) use ($column) {
                        $table->decimal($column, 15, 2)->change();
                    });
                }
            }
        }

        if (Schema::hasColumn('sales', 'payment_status')) {
            DB::table('sales')->where('payment_status', 'paid')->update(['payment_status' => 'PAID']);
            DB::table('sales')->where('payment_status', 'unpaid')->update(['payment_status' => 'UNPAID']);
            DB::table('sales')->where('payment_status', 'partial')->update(['payment_status' => 'PARTIAL']);

            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });

            Schema::table('sales', function (Blueprint $table) {
                $table->enum('payment_status', ['PAID', 'UNPAID', 'PARTIAL'])->default('UNPAID')->after('total');
            });
        }

        if (Schema::hasColumn('sales', 'sold_at')) {
            DB::table('sales')->whereNull('sold_at')->update(['sold_at' => DB::raw('created_at')]);
        }

        if (Schema::hasColumn('sales', 'subtotal')) {
            // SQLite: use total_amount if total doesn't exist
            $totalColumn = Schema::hasColumn('sales', 'total') ? 'total' : 'total_amount';
            if (Schema::hasColumn('sales', $totalColumn)) {
                DB::table('sales')->where('subtotal', 0)->update([
                    'subtotal' => DB::raw("COALESCE({$totalColumn}, 0)"),
                ]);
            }
        }

        $this->dropColumnsIfExist('sales', ['md5', 'status']);
    }

    private function alignSaleItems(): void
    {
        if (!Schema::hasTable('sale_items')) {
            return;
        }

        // SQLite: skip column changes without doctrine/dbal
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasColumn('sale_items', 'quantity')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('quantity', 15, 2)->change();
            });
        }

        foreach (['unit_price', 'discount', 'total'] as $column) {
            if (Schema::hasColumn('sale_items', $column)) {
                Schema::table('sale_items', function (Blueprint $table) use ($column) {
                    $table->decimal($column, 15, 2)->change();
                });
            }
        }
    }

    private function alignPayments(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'sale_id')) {
                $table->foreignId('sale_id')->nullable()->after('id')->constrained('sales')->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('reference_no');
            }
        });

        if (Schema::hasColumn('payments', 'payment_type')) {
            DB::table('payments')->where('payment_type', 'income')->update(['payment_type' => 'INCOME']);
            DB::table('payments')->where('payment_type', 'expense')->update(['payment_type' => 'EXPENSE']);

            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('payment_type');
            });

            Schema::table('payments', function (Blueprint $table) {
                $table->enum('type', ['INCOME', 'EXPENSE'])->after('sale_id');
            });
        }

        if (Schema::hasColumn('payments', 'amount')) {
            // SQLite: skip column change without doctrine/dbal
            if (DB::getDriverName() === 'pgsql') {
                Schema::table('payments', function (Blueprint $table) {
                    $table->decimal('amount', 15, 2)->change();
                });
            }
        }

        if (
            Schema::hasColumn('payments', 'reference_type')
            && Schema::hasColumn('payments', 'reference_id')
            && Schema::hasColumn('payments', 'sale_id')
        ) {
            DB::table('payments')
                ->whereNull('sale_id')
                ->where(function ($query) {
                    $query->where('reference_type', 'like', '%Sale%')
                        ->orWhere('reference_type', 'sale');
                })
                ->orderBy('id')
                ->chunkById(100, function ($payments) {
                    foreach ($payments as $payment) {
                        DB::table('payments')
                            ->where('id', $payment->id)
                            ->update(['sale_id' => $payment->reference_id]);
                    }
                });
        }

        $this->dropColumnsIfExist('payments', [
            'reference_type',
            'reference_id',
            'paid_to_from',
            'bill_number',
            'status',
            'md5',
        ]);
    }

    private function alignStockTransactions(): void
    {
        if (!Schema::hasTable('stock_transactions')) {
            Schema::create('stock_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no')->unique();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->enum('type', [
                    'PURCHASE',
                    'SALE',
                    'ADJUSTMENT',
                    'TRANSFER_IN',
                    'TRANSFER_OUT',
                ]);
                $table->decimal('quantity', 15, 2);
                $table->decimal('unit_cost', 15, 2)->nullable();
                $table->decimal('total_cost', 15, 2)->nullable();
                $table->unsignedBigInteger('related_id')->nullable();
                $table->string('related_type')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });

            return;
        }

        // SQLite: skip complex column changes without doctrine/dbal
        if (DB::getDriverName() === 'pgsql') {
            if (Schema::hasColumn('stock_transactions', 'reference') && !Schema::hasColumn('stock_transactions', 'reference_no')) {
                Schema::table('stock_transactions', function (Blueprint $table) {
                    $table->renameColumn('reference', 'reference_no');
                });
            }

            if (Schema::hasColumn('stock_transactions', 'type')) {
                DB::table('stock_transactions')->where('type', 'IN')->update(['type' => 'PURCHASE']);
                DB::table('stock_transactions')->where('type', 'OUT')->update(['type' => 'SALE']);
                DB::table('stock_transactions')->where('type', 'TRANSFER')->update(['type' => 'TRANSFER_IN']);

                Schema::table('stock_transactions', function (Blueprint $table) {
                    $table->dropColumn('type');
                });

                Schema::table('stock_transactions', function (Blueprint $table) {
                    $table->enum('type', [
                        'PURCHASE',
                        'SALE',
                        'ADJUSTMENT',
                        'TRANSFER_IN',
                        'TRANSFER_OUT',
                    ])->after('product_id');
                });
            }

            if (Schema::hasColumn('stock_transactions', 'quantity')) {
                Schema::table('stock_transactions', function (Blueprint $table) {
                    $table->decimal('quantity', 15, 2)->change();
                });
            }
        }
    }

    private function createStockAdjustments(): void
    {
        if (!Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->foreignId('adjusted_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('stock_adjustment_items')) {
            Schema::create('stock_adjustment_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('old_quantity', 15, 2);
                $table->decimal('new_quantity', 15, 2);
                $table->decimal('difference', 15, 2);
                $table->timestamps();
            });
        }
    }

    private function createTransfers(): void
    {
        if (!Schema::hasTable('transfers')) {
            Schema::create('transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'COMPLETED'])->default('PENDING');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transfer_items')) {
            Schema::create('transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id')->constrained('transfers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity', 15, 2);
                $table->timestamps();
            });
        }
    }
};
