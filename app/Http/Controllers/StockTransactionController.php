<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class StockTransactionController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', StockTransaction::class);

        try {
            $perPage = min($request->query('per_page', 15), 100);

            $query = StockTransaction::with([
                'product:id,name,sku',
                'warehouse:id,name,code',
                'creator:id,name',
            ])->latest();

            if ($request->filled('type')) {
                $query->where('type', strtoupper($request->query('type')));
            }

            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->query('warehouse_id'));
            }

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->query('product_id'));
            }

            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereBetween('created_at', [
                    $request->query('from_date'),
                    $request->query('to_date'),
                ]);
            }

            return ResponseHelper::success(
                'Stock transactions retrieved successfully',
                $query->paginate($perPage)
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $transaction = StockTransaction::with([
                'product',
                'warehouse',
                'creator:id,name,email',
            ])->findOrFail($id);

            $this->authorize('view', $transaction);

            return ResponseHelper::success('Stock transaction retrieved successfully', $transaction);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function purchase(StorePurchaseRequest $request)
    {
        try {
            $this->authorize('create', StockTransaction::class);

            $validated = $request->validated();

            $this->inventoryService->recordPurchase(
                (int) $validated['warehouse_id'],
                $validated['items'],
                $validated['notes'] ?? null,
                $request->user()->id
            );

            $transactions = StockTransaction::with(['product:id,name', 'warehouse:id,name'])
                ->where('warehouse_id', $validated['warehouse_id'])
                ->where('type', StockTransaction::TYPE_PURCHASE)
                ->latest()
                ->limit(count($validated['items']))
                ->get();

            return ResponseHelper::success('Purchase recorded successfully', [
                'warehouse_id' => $validated['warehouse_id'],
                'items_count' => count($validated['items']),
                'transactions' => $transactions,
            ], 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function overview()
    {
        $this->authorize('viewAny', StockTransaction::class);

        try {
            return ResponseHelper::success(
                'Inventory overview retrieved successfully',
                $this->inventoryService->getInventoryOverview()
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
