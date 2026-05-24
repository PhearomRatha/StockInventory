<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\StockAdjustment;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', StockAdjustment::class);

        try {
            $perPage = min($request->query('per_page', 15), 100);

            $query = StockAdjustment::with([
                'warehouse:id,name,code',
                'adjustedBy:id,name',
                'items.product:id,name,sku',
            ])->latest();

            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->query('warehouse_id'));
            }

            return ResponseHelper::success(
                'Stock adjustments retrieved successfully',
                $query->paginate($perPage)
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $adjustment = StockAdjustment::with([
                'warehouse',
                'adjustedBy:id,name',
                'items.product',
            ])->findOrFail($id);

            $this->authorize('view', $adjustment);

            return ResponseHelper::success('Stock adjustment retrieved successfully', $adjustment);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        try {
            $this->authorize('create', StockAdjustment::class);

            $validated = $request->validated();

            $adjustment = $this->inventoryService->createAdjustment(
                (int) $validated['warehouse_id'],
                $validated['reason'],
                $validated['items'],
                $validated['notes'] ?? null,
                $request->user()->id
            );

            return ResponseHelper::success('Stock adjustment recorded successfully', $adjustment, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function overview()
    {
        $this->authorize('viewAny', StockAdjustment::class);

        try {
            $suppliers = \App\Models\Suppliers::select('id', 'name', 'contact', 'email', 'phone', 'address')
                ->get();
            $products = \App\Models\Products::select('id', 'name', 'sku', 'barcode', 'price', 'cost', 'reorder_level')
                ->get();
            $users = \App\Models\User::select('id', 'name', 'email')
                ->get();
            $stockInHistory = \App\Models\StockAdjustment::with(['items.product', 'warehouse', 'adjustedBy'])
                ->latest()
                ->get();

            return ResponseHelper::success(
                'Stock In overview retrieved successfully',
                [
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'users' => $users,
                    'stock_history' => $stockInHistory,
                ]
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
