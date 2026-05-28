<?php

namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Models\Products;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransaction;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryService
{
    public function resolveWarehouseId(?int $warehouseId = null): int
    {
        if ($warehouseId) {
            return Warehouse::where('id', $warehouseId)->where('status', true)->value('id')
                ?? throw new \InvalidArgumentException('Warehouse not found or inactive.');
        }

        $id = Warehouse::where('status', true)->orderBy('id')->value('id');

        if (!$id) {
            throw new \RuntimeException('No active warehouse configured.');
        }

        return $id;
    }

    public function getStockLevel(int $productId, ?int $warehouseId = null): float
    {
        $query = WarehouseProduct::where('product_id', $productId);

        if ($warehouseId) {
            return (float) ($query->where('warehouse_id', $warehouseId)->value('quantity') ?? 0);
        }

        return (float) $query->sum('quantity');
    }

    public function hasSufficientStock(int $productId, $quantity, ?int $warehouseId = null): bool
    {
        return $this->getStockLevel($productId, $warehouseId) >= $quantity;
    }

    public function increaseStock(
        int $productId,
        $quantity,
        ?int $warehouseId = null,
        string $type = StockTransaction::TYPE_PURCHASE,
        ?string $notes = null,
        ?int $userId = null,
        ?int $relatedId = null,
        ?string $relatedType = null
    ): Products {
        return $this->adjustStock($productId, abs($quantity), $type, $warehouseId, $notes, $userId, $relatedId, $relatedType);
    }

    public function decreaseStock(
        int $productId,
        $quantity,
        ?int $warehouseId = null,
        string $type = StockTransaction::TYPE_SALE,
        ?string $notes = null,
        ?int $userId = null,
        ?int $relatedId = null,
        ?string $relatedType = null
    ): Products {
        return $this->adjustStock($productId, -abs($quantity), $type, $warehouseId, $notes, $userId, $relatedId, $relatedType);
    }

    public function reserveStock(
        int $productId,
        $quantity,
        ?int $warehouseId = null,
        ?string $description = null,
        ?int $userId = null,
        ?int $relatedId = null,
        ?string $relatedType = null
    ): Products {
        return $this->decreaseStock(
            $productId,
            $quantity,
            $warehouseId,
            StockTransaction::TYPE_SALE,
            $description,
            $userId,
            $relatedId,
            $relatedType
        );
    }

    protected function adjustStock(
        int $productId,
        float $delta,
        string $type,
        ?int $warehouseId,
        ?string $notes,
        ?int $userId,
        ?int $relatedId,
        ?string $relatedType
    ): Products {
        return DB::transaction(function () use ($productId, $delta, $type, $warehouseId, $notes, $userId, $relatedId, $relatedType) {
            $warehouseId = $this->resolveWarehouseId($warehouseId);
            $userId = $userId ?: auth()->id();

            Products::lockForUpdate()->findOrFail($productId);

            $row = WarehouseProduct::lockForUpdate()->firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['quantity' => 0, 'updated_at' => now()]
            );

            $oldQuantity = (float) $row->quantity;
            $newQuantity = $oldQuantity + $delta;

            if ($newQuantity < 0) {
                $product = Products::find($productId);
                throw new \Exception(
                    "Insufficient stock for {$product->name}. Available: {$oldQuantity}, requested: " . abs($delta)
                );
            }

            $row->update(['quantity' => $newQuantity, 'updated_at' => now()]);

            $product = Products::findOrFail($productId);
            $unitCost = $product->cost ?? 0;

            StockTransaction::create([
                'reference_no' => $this->generateReferenceNo($type),
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $type,
                'quantity' => abs($delta),
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost * abs($delta),
                'related_id' => $relatedId,
                'related_type' => $relatedType,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            ActivityLogHelper::log(
                'inventory',
                sprintf(
                    'Stock %s: %s%.2f units of %s (%s → %s)',
                    $delta >= 0 ? 'increased' : 'decreased',
                    $delta >= 0 ? '+' : '',
                    $delta,
                    $product->name,
                    $oldQuantity,
                    $newQuantity
                ),
                $delta >= 0 ? 'stock_in' : 'stock_out'
            );

            $this->clearCaches();

            return $product->fresh(['warehouseProducts']);
        });
    }

    public function recordPurchase(
        int $warehouseId,
        array $items,
        ?string $notes = null,
        ?int $userId = null
    ): array {
        $userId = $userId ?: auth()->id();
        $warehouseId = $this->resolveWarehouseId($warehouseId);
        $results = [];

        DB::transaction(function () use ($warehouseId, $items, $notes, $userId, &$results) {
            foreach ($items as $item) {
                $qty = (float) $item['quantity'];
                $unitCost = isset($item['unit_cost']) ? (float) $item['unit_cost'] : null;

                $product = $this->increaseStock(
                    (int) $item['product_id'],
                    $qty,
                    $warehouseId,
                    StockTransaction::TYPE_PURCHASE,
                    $notes,
                    $userId
                );

                if ($unitCost !== null) {
                    StockTransaction::where('product_id', $product->id)
                        ->where('warehouse_id', $warehouseId)
                        ->where('type', StockTransaction::TYPE_PURCHASE)
                        ->latest('id')
                        ->limit(1)
                        ->update([
                            'unit_cost' => $unitCost,
                            'total_cost' => $unitCost * $qty,
                        ]);
                }

                $results[] = $product;
            }
        });

        return $results;
    }

    public function createAdjustment(
        int $warehouseId,
        string $reason,
        array $items,
        ?string $notes = null,
        ?int $userId = null
    ): StockAdjustment {
        $userId = $userId ?: auth()->id();
        $warehouseId = $this->resolveWarehouseId($warehouseId);

        return DB::transaction(function () use ($warehouseId, $reason, $items, $notes, $userId) {
            $adjustment = StockAdjustment::create([
                'warehouse_id' => $warehouseId,
                'reason' => $reason,
                'notes' => $notes,
                'adjusted_by' => $userId,
            ]);

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $newQuantity = (float) $item['new_quantity'];
                $oldQuantity = $this->getStockLevel($productId, $warehouseId);
                $difference = $newQuantity - $oldQuantity;

                StockAdjustmentItem::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id' => $productId,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'difference' => $difference,
                ]);

                if ($difference > 0) {
                    $this->increaseStock(
                        $productId,
                        $difference,
                        $warehouseId,
                        StockTransaction::TYPE_ADJUSTMENT,
                        $reason,
                        $userId,
                        $adjustment->id,
                        StockAdjustment::class
                    );
                } elseif ($difference < 0) {
                    $this->decreaseStock(
                        $productId,
                        abs($difference),
                        $warehouseId,
                        StockTransaction::TYPE_ADJUSTMENT,
                        $reason,
                        $userId,
                        $adjustment->id,
                        StockAdjustment::class
                    );
                }
            }

            ActivityLogHelper::log(
                'inventory',
                "Stock adjustment #{$adjustment->id}: {$reason}",
                'adjustment'
            );

            return $adjustment->load(['items.product', 'warehouse', 'adjustedBy']);
        });
    }

    public function createTransfer(
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items,
        ?string $notes = null,
        ?int $userId = null
    ): Transfer {
        $userId = $userId ?: auth()->id();

        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException('Source and destination warehouses must be different.');
        }

        $this->resolveWarehouseId($fromWarehouseId);
        $this->resolveWarehouseId($toWarehouseId);

        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $items, $notes, $userId) {
            foreach ($items as $item) {
                if (!$this->hasSufficientStock((int) $item['product_id'], $item['quantity'], $fromWarehouseId)) {
                    $product = Products::find($item['product_id']);
                    throw new \Exception("Insufficient stock for transfer: {$product?->name}");
                }
            }

            $transfer = Transfer::create([
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'status' => Transfer::STATUS_PENDING,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                ]);
            }

            ActivityLogHelper::log(
                'inventory',
                "Transfer #{$transfer->id} created (pending)",
                'transfer'
            );

            return $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse', 'createdBy']);
        });
    }

    public function approveTransfer(int $transferId, ?int $userId = null): Transfer
    {
        $transfer = Transfer::findOrFail($transferId);

        if ($transfer->status !== Transfer::STATUS_PENDING) {
            throw new \Exception('Only pending transfers can be approved.');
        }

        $transfer->update([
            'status' => Transfer::STATUS_APPROVED,
            'approved_by' => $userId ?: auth()->id(),
        ]);

        return $transfer->fresh(['items.product', 'fromWarehouse', 'toWarehouse']);
    }

    public function rejectTransfer(int $transferId, ?int $userId = null): Transfer
    {
        $transfer = Transfer::findOrFail($transferId);

        if (!in_array($transfer->status, [Transfer::STATUS_PENDING, Transfer::STATUS_APPROVED], true)) {
            throw new \Exception('Transfer cannot be rejected in its current status.');
        }

        $transfer->update([
            'status' => Transfer::STATUS_REJECTED,
            'approved_by' => $userId ?: auth()->id(),
        ]);

        return $transfer->fresh();
    }

    public function completeTransfer(int $transferId, ?int $userId = null): Transfer
    {
        $userId = $userId ?: auth()->id();
        $transfer = Transfer::with('items')->findOrFail($transferId);

        if (!in_array($transfer->status, [Transfer::STATUS_PENDING, Transfer::STATUS_APPROVED], true)) {
            throw new \Exception('Transfer cannot be completed in its current status.');
        }

        return DB::transaction(function () use ($transfer, $userId) {
            foreach ($transfer->items as $item) {
                $this->decreaseStock(
                    $item->product_id,
                    $item->quantity,
                    $transfer->from_warehouse_id,
                    StockTransaction::TYPE_TRANSFER_OUT,
                    $transfer->notes,
                    $userId,
                    $transfer->id,
                    Transfer::class
                );

                $this->increaseStock(
                    $item->product_id,
                    $item->quantity,
                    $transfer->to_warehouse_id,
                    StockTransaction::TYPE_TRANSFER_IN,
                    $transfer->notes,
                    $userId,
                    $transfer->id,
                    Transfer::class
                );
            }

            $transfer->update([
                'status' => Transfer::STATUS_COMPLETED,
                'approved_by' => $transfer->approved_by ?: $userId,
            ]);

            ActivityLogHelper::log(
                'inventory',
                "Transfer #{$transfer->id} completed",
                'transfer'
            );

            return $transfer->fresh(['items.product', 'fromWarehouse', 'toWarehouse', 'createdBy', 'approvedBy']);
        });
    }

    public function createStockOut(
        int $warehouseId,
        array $items,
        ?string $reason = null,
        ?string $notes = null,
        ?int $userId = null
    ): StockTransaction {
        $userId = $userId ?: auth()->id();
        $warehouseId = $this->resolveWarehouseId($warehouseId);
        $lastTransactionId = null;

        DB::transaction(function () use ($warehouseId, $items, $reason, $notes, $userId, &$lastTransactionId) {
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (float) $item['quantity'];

                $this->decreaseStock(
                    $productId,
                    $quantity,
                    $warehouseId,
                    StockTransaction::TYPE_SALE,
                    $notes ?? $reason,
                    $userId
                );

                $lastTransactionId = StockTransaction::where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->where('type', StockTransaction::TYPE_SALE)
                    ->latest('id')
                    ->value('id');
            }

            ActivityLogHelper::log(
                'inventory',
                "Stock out recorded: " . count($items) . " items",
                'stock_out'
            );
        });

        return StockTransaction::with(['product', 'warehouse'])->findOrFail($lastTransactionId);
    }

    public function getWarehouseStock(int $warehouseId, int $perPage = 15)
    {
        $warehouseId = $this->resolveWarehouseId($warehouseId);

        return WarehouseProduct::where('warehouse_id', $warehouseId)
            ->with(['product:id,name,sku,price,cost,reorder_level,status'])
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function getInventoryOverview(): array
    {
        return Cache::remember('inventory_overview', 300, function () {
            $purchases = StockTransaction::where('type', StockTransaction::TYPE_PURCHASE)
                ->selectRaw('COALESCE(SUM(quantity), 0) as total')
                ->value('total');

            $sales = StockTransaction::where('type', StockTransaction::TYPE_SALE)
                ->selectRaw('COALESCE(SUM(quantity), 0) as total')
                ->value('total');

            $recent = StockTransaction::with(['product:id,name', 'warehouse:id,name'])
                ->latest()
                ->limit(10)
                ->get(['id', 'reference_no', 'type', 'product_id', 'warehouse_id', 'quantity', 'created_at']);

            return [
                'total_purchases' => (float) $purchases,
                'total_sales_movement' => (float) $sales,
                'recent_transactions' => $recent,
            ];
        });
    }

    public function getLowStockProducts(int $limit = 50)
    {
        return Cache::remember('low_stock_products', 60, function () use ($limit) {
            return Products::query()
                ->select('products.id', 'products.name', 'products.reorder_level')
                ->selectRaw('COALESCE(SUM(warehouse_products.quantity), 0) as total_quantity')
                ->leftJoin('warehouse_products', 'warehouse_products.product_id', '=', 'products.id')
                ->where('products.status', true)
                ->groupBy('products.id', 'products.name', 'products.reorder_level')
                ->havingRaw('COALESCE(SUM(warehouse_products.quantity), 0) > 0')
                ->havingRaw('COALESCE(SUM(warehouse_products.quantity), 0) <= products.reorder_level')
                ->limit($limit)
                ->get();
        });
    }

    public function getOutOfStockProducts(int $limit = 50)
    {
        return Cache::remember('out_of_stock_products', 60, function () use ($limit) {
            return Products::query()
                ->select('products.id', 'products.name')
                ->leftJoin('warehouse_products', 'warehouse_products.product_id', '=', 'products.id')
                ->where('products.status', true)
                ->groupBy('products.id', 'products.name')
                ->havingRaw('COALESCE(SUM(warehouse_products.quantity), 0) = 0')
                ->limit($limit)
                ->get();
        });
    }

    public function getTotalStockValue(): float
    {
        return Cache::remember('total_stock_value', 3600, function () {
            return (float) DB::table('warehouse_products')
                ->join('products', 'products.id', '=', 'warehouse_products.product_id')
                ->selectRaw('COALESCE(SUM(warehouse_products.quantity * products.price), 0) as total_value')
                ->value('total_value');
        });
    }

    protected function generateReferenceNo(string $type): string
    {
        return strtoupper($type) . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }

    protected function clearCaches(): void
    {
        foreach ([
            'inventory_overview',
            'stock_report',
            'low_stock_products',
            'out_of_stock_products',
            'total_stock_value',
        ] as $key) {
            Cache::forget($key);
        }
    }
}
