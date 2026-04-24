<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreStockInRequest;
use App\Models\Products as Product;
use App\Models\Stock_ins as StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StockInsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);

            // OPTIMIZED: Use pagination + select columns (received_date, not date)
            $stockIns = StockIn::select('id', 'product_id', 'supplier_id', 'quantity', 'received_date', 'notes', 'created_at')
                ->with([
                    'product:id,name,sku',  // Only needed product fields
                    'supplier:id,name',     // Only needed supplier fields
                ])
                ->latest()
                ->paginate(min($perPage, 100));

            return ResponseHelper::success('Stock ins retrieved successfully', $stockIns);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function overview()
    {
        try {
            // OPTIMIZED: Cache dashboard for 5 minutes
            $data = Cache::remember('stock_in_dashboard', 300, function () {
                $totalStockIn = StockIn::sum('quantity');

                // OPTIMIZED: Select only needed columns (received_date, not date)
                $stockIns = StockIn::select('id', 'product_id', 'supplier_id', 'quantity', 'received_date', 'created_at')
                    ->with(['product:id,name', 'supplier:id,name'])
                    ->latest()
                    ->take(10)
                    ->get();

                return [
                    'total_stock_in' => $totalStockIn,
                    'recent_stock_ins' => $stockIns,
                ];
            });

            return ResponseHelper::success('Stock in overview retrieved successfully', $data);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreStockInRequest $request)
    {
        try {
            $validated = $request->validated();

            // OPTIMIZED: Use single query to get product
            $product = Product::findOrFail($validated['product_id']);

            $stockIn = StockIn::create($validated);

            // Update product stock using increment
            Product::where('id', $validated['product_id'])
                ->increment('stock_quantity', $validated['quantity']);

            ActivityLogHelper::log('stock_in', "Stock In: {$validated['quantity']} units of {$product->name}");

            // Clear caches
            Cache::forget('stock_in_dashboard');
            Cache::forget('stock_report');

            return ResponseHelper::success('Stock in created successfully', $stockIn, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $stockIn = StockIn::findOrFail($id);

            $validated = $request->validate([
                'product_id' => 'sometimes|required|exists:products,id',
                'supplier_id' => 'sometimes|required|exists:suppliers,id',
                'quantity' => 'sometimes|required|integer|min:1',
                'date' => 'sometimes|required|date',
                'notes' => 'nullable|string',
            ]);

            $stockIn->update($validated);

            // Clear caches
            Cache::forget('stock_in_dashboard');

            return ResponseHelper::success('Stock in updated successfully', $stockIn);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $stockIn = StockIn::findOrFail($id);
            $stockIn->delete();

            // Clear caches
            Cache::forget('stock_in_dashboard');

            return ResponseHelper::success('Stock in deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function totalStockIn()
    {
        try {
            // OPTIMIZED: Cache the total
            $total = Cache::remember('stock_in_total', 60, function () {
                return StockIn::sum('quantity');
            });

            return ResponseHelper::success('Total stock in retrieved successfully', ['total_stock_in' => $total]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
