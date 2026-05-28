<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class StockOutsController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        try {
            $perPage = min($request->query('per_page', 15), 100);

            $query = StockTransaction::with([
                'product:id,name,sku,price',
                'warehouse:id,name,code',
                'creator:id,name',
            ])->where('type', StockTransaction::TYPE_SALE)->latest();

            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->query('warehouse_id'));
            }

            return ResponseHelper::success(
                'Stock outs retrieved successfully',
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
            ])->where('type', StockTransaction::TYPE_SALE)->findOrFail($id);

            $this->authorize('view', $transaction);
            return ResponseHelper::success('Stock out retrieved successfully', $transaction);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $this->authorize('create', StockTransaction::class);
        try {
            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'customer_id' => 'nullable|exists:customers,id',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'reason' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $stockOut = $this->inventoryService->createStockOut(
                (int) $validated['warehouse_id'],
                $validated['items'],
                $validated['reason'] ?? null,
                $validated['notes'] ?? null,
                $request->user()->id
            );

            return ResponseHelper::success('Stock out recorded successfully', $stockOut, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', StockTransaction::class);

        try {
            $limit = min((int) $request->query('limit', 100), 500);

            $products = \App\Models\Products::select('id', 'name', 'sku', 'price')
                ->withSum('warehouseProducts as total_qty', 'quantity')
                ->orderBy('name')
                ->limit($limit)
                ->get();

            $customers = \App\Models\Customers::select('id', 'name', 'email', 'phone')
                ->orderBy('name')
                ->limit($limit)
                ->get();

            $users = \App\Models\User::select('id', 'name', 'email')
                ->orderBy('name')
                ->limit($limit)
                ->get();

            $stockOuts = StockTransaction::with(['product:id,name,sku,price', 'warehouse:id,name,code', 'creator:id,name'])
                ->where('type', StockTransaction::TYPE_SALE)
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'customer_id' => null,
                        'customer_name' => 'N/A',
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'N/A',
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_cost,
                        'total_amount' => (float) $item->total_cost,
                        'date' => $item->created_at?->format('Y-m-d'),
                        'notes' => $item->notes,
                    ];
                });

            return ResponseHelper::success('Stock out dashboard retrieved successfully', [
                'products' => $products,
                'customers' => $customers,
                'users' => $users,
                'stockOuts' => $stockOuts,
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function receipt($id)
    {
        try {
            $transaction = StockTransaction::with(['product', 'warehouse', 'creator:id,name'])
                ->where('type', StockTransaction::TYPE_SALE)
                ->findOrFail($id);

            return ResponseHelper::success('Receipt retrieved successfully', $transaction);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
