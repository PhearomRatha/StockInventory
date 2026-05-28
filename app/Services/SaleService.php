<?php

namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Models\Customers as Customer;
use App\Models\Products as Product;
use App\Models\Sales as Sale;
use App\Models\StockTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Sale::select(
            'id',
            'customer_id',
            'warehouse_id',
            'sold_by',
            'invoice_number',
            'subtotal',
            'discount',
            'tax',
            'total',
            'payment_status',
            'payment_method',
            'notes',
            'sold_at',
            'created_at'
        )
            ->with([
                'customer:id,name',
                'soldBy:id,name',
                'warehouse:id,name,code',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): Sale
    {
        return Sale::with([
            'customer:id,name,email',
            'soldBy:id,name',
            'warehouse:id,name,code',
            'saleItems.product:id,name,price,sku',
        ])->findOrFail($id);
    }

    public function create(array $validated, int $userId, string $paymentStatus = 'UNPAID'): Sale
    {
        $warehouseId = (int) $validated['warehouse_id'];

        return DB::transaction(function () use ($validated, $userId, $warehouseId, $paymentStatus) {
            $productIds = collect($validated['items'])->pluck('product_id')->unique()->values();
            $products = Product::whereIn('id', $productIds)
                ->get(['id', 'name', 'price'])
                ->keyBy('id');

            $subtotal = 0;
            $saleItemsData = [];

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw new \Exception('Product not found.');
                }

                $qty = (float) $item['quantity'];
                $unitPrice = (float) $product->price;
                $itemTotal = $unitPrice * $qty;
                $subtotal += $itemTotal;

                $saleItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount' => 0,
                    'total' => $itemTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $discount = (float) ($validated['discount'] ?? 0);
            $tax = (float) ($validated['tax'] ?? 0);
            $total = $subtotal - $discount + $tax;

            $sale = Sale::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'invoice_number' => $this->generateInvoiceNumber(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'payment_status' => $paymentStatus,
                'payment_method' => $validated['payment_method'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'sold_by' => $userId,
                'sold_at' => now(),
            ]);

            $sale->saleItems()->createMany($saleItemsData);

            foreach ($validated['items'] as $item) {
                $this->inventoryService->decreaseStock(
                    (int) $item['product_id'],
                    (float) $item['quantity'],
                    $warehouseId,
                    StockTransaction::TYPE_SALE,
                    "Sale #{$sale->id} deduction",
                    $userId,
                    $sale->id,
                    Sale::class
                );
            }

            ActivityLogHelper::log('sale', "Created Sale #{$sale->id} in warehouse {$warehouseId}");

            return $sale->load('saleItems.product:id,name', 'customer:id,name', 'soldBy:id,name', 'warehouse:id,name');
        });
    }

    public function searchProducts(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $stockSubquery = DB::table('warehouse_products')
            ->select('product_id', DB::raw('SUM(quantity) as stock_quantity'))
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) > 0');

        return Product::select('products.id', 'products.name', 'products.price', 'stock.stock_quantity')
            ->joinSub($stockSubquery, 'stock', 'stock.product_id', '=', 'products.id')
            ->when($search, fn ($query) => $query->where('products.name', 'like', "%{$search}%"))
            ->orderBy('products.name')
            ->paginate($perPage);
    }

    public function searchCustomers(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::select('id', 'name')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    protected function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}
