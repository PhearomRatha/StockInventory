<?php

namespace Database\Seeders;

use App\Models\Categories;
use App\Models\Customers;
use App\Models\Permission;
use App\Models\Products;
use App\Models\Roles;
use App\Models\Sales;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransaction;
use App\Models\Suppliers;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedRolesAndPermissions();
            $users = $this->seedUsers();
            $categories = $this->seedCategories();
            $suppliers = $this->seedSuppliers();
            $customers = $this->seedCustomers();
            $warehouses = $this->seedWarehouses($users['admin']->id);
            $products = $this->seedProducts($categories, $suppliers);
            $this->seedWarehouseStock($warehouses, $products, $users['admin']->id);
            $this->seedStockTransactions($warehouses, $products, $users['admin']->id);
            $this->seedStockAdjustment($warehouses['main'], $products, $users['manager']->id);
            $this->seedTransfer($warehouses, $products, $users['admin']->id, $users['manager']->id);
            $this->seedSales($customers, $warehouses, $products, $users['staff']->id);
        });
    }

    private function seedRolesAndPermissions(): void
    {
        $admin = Roles::firstOrCreate(['name' => Roles::ROLE_ADMIN]);
        $manager = Roles::firstOrCreate(['name' => Roles::ROLE_MANAGER]);
        $staff = Roles::firstOrCreate(['name' => Roles::ROLE_STAFF]);
        $casher = Roles::firstOrCreate(['name' => Roles::ROLE_CASHER]);

        if (Permission::count() === 0) {
            foreach (Permission::MODULES as $module) {
                foreach (Permission::ACTIONS as $action) {
                    Permission::create([
                        'module' => $module,
                        'action' => $action,
                        'description' => "{$module}.{$action}",
                    ]);
                }
            }
        }

        $allPermissionIds = Permission::pluck('id')->all();

        $admin->permissions()->sync($allPermissionIds);

        $managerPermissions = Permission::query()
            ->where(function ($q) {
                $q->whereIn('module', [
                    'dashboard', 'products', 'categories', 'suppliers', 'customers',
                    'inventory', 'sales', 'payments', 'reports', 'warehouses',
                ])->whereIn('action', ['view', 'create', 'update', 'approve', 'export']);
            })
            ->pluck('id')
            ->all();
        $manager->permissions()->sync($managerPermissions);

        $staffPermissions = Permission::query()
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('action', 'view')
                        ->whereIn('module', ['dashboard', 'products', 'customers', 'inventory', 'sales', 'payments']);
                })->orWhere(function ($inner) {
                    $inner->where('module', 'sales')->where('action', 'create');
                });
            })
            ->pluck('id')
            ->all();
        $staff->permissions()->sync($staffPermissions);

        $casherPermissions = Permission::query()
            ->where(function ($q) {
                $q->where('module', 'sales')->whereIn('action', ['view', 'create']);
            })
            ->pluck('id')
            ->all();
        $casher->permissions()->sync($casherPermissions);
    }

    private function seedUsers(): array
    {
        $adminRole = Roles::where('name', Roles::ROLE_ADMIN)->first();
        $managerRole = Roles::where('name', Roles::ROLE_MANAGER)->first();
        $staffRole = Roles::where('name', Roles::ROLE_STAFF)->first();

        $admin = User::updateOrCreate(
            ['email' => 'admin@stockinventory.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'status' => User::STATUS_ACTIVE,
                'role_id' => $adminRole->id,
            ]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@stockinventory.com'],
            [
                'name' => 'Warehouse Manager',
                'password' => Hash::make('password123'),
                'status' => User::STATUS_ACTIVE,
                'role_id' => $managerRole->id,
            ]
        );

        $staff = User::updateOrCreate(
            ['email' => 'staff@stockinventory.com'],
            [
                'name' => 'Sales Staff',
                'password' => Hash::make('password123'),
                'status' => User::STATUS_ACTIVE,
                'role_id' => $staffRole->id,
            ]
        );

        return compact('admin', 'manager', 'staff');
    }

    private function seedCategories(): array
    {
        $names = ['Electronics', 'Office Supplies', 'Food & Beverage', 'Clothing', 'Hardware'];

        $categories = [];
        foreach ($names as $name) {
            $categories[] = Categories::firstOrCreate(
                ['name' => $name],
                ['description' => "{$name} category"]
            );
        }

        return $categories;
    }

    private function seedSuppliers(): array
    {
        $data = [
            ['name' => 'TechSource Ltd', 'company' => 'TechSource', 'phone' => '+855-12-111-001', 'email' => 'sales@techsource.com'],
            ['name' => 'OfficeMart', 'company' => 'OfficeMart Co.', 'phone' => '+855-12-111-002', 'email' => 'order@officemart.com'],
            ['name' => 'FreshFoods', 'company' => 'FreshFoods Import', 'phone' => '+855-12-111-003', 'email' => 'buy@freshfoods.com'],
        ];

        return array_map(fn ($row) => Suppliers::firstOrCreate(['name' => $row['name']], $row + ['status' => true]), $data);
    }

    private function seedCustomers(): array
    {
        $data = [
            ['name' => 'Walk-in Customer', 'type' => 'retail', 'phone' => null],
            ['name' => 'ABC Retail Shop', 'type' => 'wholesale', 'phone' => '+855-98-100-001', 'email' => 'abc@shop.com'],
            ['name' => 'City Cafe', 'type' => 'business', 'phone' => '+855-98-100-002', 'email' => 'cafe@city.com'],
        ];

        return array_map(fn ($row) => Customers::firstOrCreate(['name' => $row['name']], $row), $data);
    }

    private function seedWarehouses(int $adminId): array
    {
        $main = Warehouse::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Warehouse',
                'location' => 'Phnom Penh',
                'status' => true,
                'created_by' => $adminId,
            ]
        );

        $branch = Warehouse::firstOrCreate(
            ['code' => 'BR01'],
            [
                'name' => 'Branch Warehouse 1',
                'location' => 'Siem Reap',
                'status' => true,
                'created_by' => $adminId,
            ]
        );

        return ['main' => $main, 'branch' => $branch];
    }

    private function seedProducts(array $categories, array $suppliers): array
    {
        $items = [
            ['name' => 'Wireless Mouse', 'sku' => 'SKU-MOUSE-001', 'category' => 0, 'supplier' => 0, 'cost' => 8.50, 'price' => 15.99, 'reorder' => 20],
            ['name' => 'USB Keyboard', 'sku' => 'SKU-KBD-001', 'category' => 0, 'supplier' => 0, 'cost' => 12.00, 'price' => 24.99, 'reorder' => 15],
            ['name' => 'A4 Paper Ream', 'sku' => 'SKU-PAPER-001', 'category' => 1, 'supplier' => 1, 'cost' => 3.20, 'price' => 5.50, 'reorder' => 50],
            ['name' => 'Ballpoint Pen Box', 'sku' => 'SKU-PEN-001', 'category' => 1, 'supplier' => 1, 'cost' => 2.00, 'price' => 4.25, 'reorder' => 30],
            ['name' => 'Bottled Water 24pk', 'sku' => 'SKU-WATER-001', 'category' => 2, 'supplier' => 2, 'cost' => 4.50, 'price' => 7.99, 'reorder' => 40],
            ['name' => 'Instant Coffee 200g', 'sku' => 'SKU-COFFEE-001', 'category' => 2, 'supplier' => 2, 'cost' => 5.80, 'price' => 9.50, 'reorder' => 25],
            ['name' => 'Cotton T-Shirt', 'sku' => 'SKU-TSHIRT-001', 'category' => 3, 'supplier' => null, 'cost' => 6.00, 'price' => 12.00, 'reorder' => 20],
            ['name' => 'Hammer 500g', 'sku' => 'SKU-HAMMER-001', 'category' => 4, 'supplier' => null, 'cost' => 7.50, 'price' => 14.00, 'reorder' => 10],
        ];

        $products = [];
        foreach ($items as $item) {
            $products[] = Products::firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'category_id' => $categories[$item['category']]->id,
                    'supplier_id' => $item['supplier'] !== null ? $suppliers[$item['supplier']]->id : null,
                    'barcode' => (string) random_int(100000000000, 999999999999),
                    'description' => "Sample product: {$item['name']}",
                    'cost' => $item['cost'],
                    'price' => $item['price'],
                    'reorder_level' => $item['reorder'],
                    'status' => true,
                    'image' => null,
                ]
            );
        }

        return $products;
    }

    private function seedWarehouseStock(array $warehouses, array $products, int $userId): void
    {
        $stockLevels = [100, 80, 200, 150, 120, 90, 60, 45];

        foreach ($products as $i => $product) {
            WarehouseProduct::updateOrCreate(
                [
                    'warehouse_id' => $warehouses['main']->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $stockLevels[$i] ?? 50,
                    'updated_at' => now(),
                ]
            );

            WarehouseProduct::updateOrCreate(
                [
                    'warehouse_id' => $warehouses['branch']->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => (int) (($stockLevels[$i] ?? 50) * 0.3),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedStockTransactions(array $warehouses, array $products, int $userId): void
    {
        if (StockTransaction::exists()) {
            return;
        }

        foreach (array_slice($products, 0, 4) as $i => $product) {
            $qty = 50 + ($i * 10);
            StockTransaction::create([
                'reference_no' => 'PURCHASE-SEED-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'warehouse_id' => $warehouses['main']->id,
                'product_id' => $product->id,
                'type' => StockTransaction::TYPE_PURCHASE,
                'quantity' => $qty,
                'unit_cost' => $product->cost,
                'total_cost' => $product->cost * $qty,
                'notes' => 'Initial purchase seed',
                'created_by' => $userId,
            ]);
        }
    }

    private function seedStockAdjustment($mainWarehouse, array $products, int $userId): void
    {
        if (StockAdjustment::exists()) {
            return;
        }

        $product = $products[0];
        $row = WarehouseProduct::where('warehouse_id', $mainWarehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $oldQty = (float) ($row->quantity ?? 0);
        $newQty = $oldQty - 5;

        $adjustment = StockAdjustment::create([
            'warehouse_id' => $mainWarehouse->id,
            'reason' => 'Cycle count correction',
            'notes' => 'Sample adjustment from seeder',
            'adjusted_by' => $userId,
        ]);

        StockAdjustmentItem::create([
            'adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'difference' => $newQty - $oldQty,
        ]);

        $row->update(['quantity' => $newQty, 'updated_at' => now()]);

        StockTransaction::create([
            'reference_no' => 'ADJUSTMENT-SEED-0001',
            'warehouse_id' => $mainWarehouse->id,
            'product_id' => $product->id,
            'type' => StockTransaction::TYPE_ADJUSTMENT,
            'quantity' => 5,
            'unit_cost' => $product->cost,
            'total_cost' => $product->cost * 5,
            'related_id' => $adjustment->id,
            'related_type' => StockAdjustment::class,
            'notes' => 'Cycle count correction',
            'created_by' => $userId,
        ]);
    }

    private function seedTransfer(array $warehouses, array $products, int $adminId, int $managerId): void
    {
        if (Transfer::exists()) {
            return;
        }

        $product = $products[1];
        $qty = 10;

        $pending = Transfer::create([
            'from_warehouse_id' => $warehouses['main']->id,
            'to_warehouse_id' => $warehouses['branch']->id,
            'status' => Transfer::STATUS_PENDING,
            'notes' => 'Pending transfer (seed)',
            'created_by' => $adminId,
        ]);

        TransferItem::create([
            'transfer_id' => $pending->id,
            'product_id' => $product->id,
            'quantity' => $qty,
        ]);

        $completed = Transfer::create([
            'from_warehouse_id' => $warehouses['main']->id,
            'to_warehouse_id' => $warehouses['branch']->id,
            'status' => Transfer::STATUS_COMPLETED,
            'notes' => 'Completed transfer (seed)',
            'created_by' => $adminId,
            'approved_by' => $managerId,
        ]);

        TransferItem::create([
            'transfer_id' => $completed->id,
            'product_id' => $products[2]->id,
            'quantity' => 5,
        ]);

        $mainRow = WarehouseProduct::where('warehouse_id', $warehouses['main']->id)
            ->where('product_id', $products[2]->id)->first();
        $branchRow = WarehouseProduct::where('warehouse_id', $warehouses['branch']->id)
            ->where('product_id', $products[2]->id)->first();

        if ($mainRow && $branchRow) {
            $mainRow->update(['quantity' => max(0, $mainRow->quantity - 5)]);
            $branchRow->update(['quantity' => $branchRow->quantity + 5]);
        }

        StockTransaction::create([
            'reference_no' => 'TRANSFER-OUT-SEED-0001',
            'warehouse_id' => $warehouses['main']->id,
            'product_id' => $products[2]->id,
            'type' => StockTransaction::TYPE_TRANSFER_OUT,
            'quantity' => 5,
            'related_id' => $completed->id,
            'related_type' => Transfer::class,
            'created_by' => $adminId,
        ]);

        StockTransaction::create([
            'reference_no' => 'TRANSFER-IN-SEED-0001',
            'warehouse_id' => $warehouses['branch']->id,
            'product_id' => $products[2]->id,
            'type' => StockTransaction::TYPE_TRANSFER_IN,
            'quantity' => 5,
            'related_id' => $completed->id,
            'related_type' => Transfer::class,
            'created_by' => $adminId,
        ]);
    }

    private function seedSales(array $customers, array $warehouses, array $products, int $staffId): void
    {
        if (Sales::exists()) {
            return;
        }

        $product = $products[0];
        $qty = 2;
        $subtotal = $product->price * $qty;
        $discount = 0;
        $tax = 0;
        $total = $subtotal - $discount + $tax;

        $sale = Sales::create([
            'customer_id' => $customers[1]->id,
            'warehouse_id' => $warehouses['main']->id,
            'invoice_number' => 'INV-SEED-000001',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'payment_status' => Sales::PAYMENT_PAID,
            'payment_method' => 'cash',
            'notes' => 'Sample sale from seeder',
            'sold_by' => $staffId,
            'sold_at' => now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $product->price,
            'discount' => 0,
            'total' => $subtotal,
        ]);

        $row = WarehouseProduct::where('warehouse_id', $warehouses['main']->id)
            ->where('product_id', $product->id)->first();
        if ($row) {
            $row->update(['quantity' => max(0, $row->quantity - $qty)]);
        }

        StockTransaction::create([
            'reference_no' => 'SALE-SEED-000001',
            'warehouse_id' => $warehouses['main']->id,
            'product_id' => $product->id,
            'type' => StockTransaction::TYPE_SALE,
            'quantity' => $qty,
            'unit_cost' => $product->cost,
            'total_cost' => $product->cost * $qty,
            'related_id' => $sale->id,
            'related_type' => Sales::class,
            'created_by' => $staffId,
        ]);
    }
}