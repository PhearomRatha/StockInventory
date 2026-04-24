<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock_outs as StockOut;
use App\Models\Products as Product;
use App\Helpers\ResponseHelper;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\Cache;

class StockOutsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            
            // OPTIMIZED: Use pagination + select columns
            $stockOuts = StockOut::select('id', 'product_id', 'customer_id', 'quantity', 'sold_date', 'remarks', 'created_at')
                ->with([
                    'product:id,name,sku',
                    'customer:id,name,email'
                ])
                ->latest()
                ->paginate(min($perPage, 100));
            
            return ResponseHelper::success('Stock outs retrieved successfully', $stockOuts);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            // OPTIMIZED: Select columns for relationships
            $stockOut = StockOut::with([
                'product:id,name,sku,price',
                'customer:id,name,email,phone'
            ])->findOrFail($id);
            return ResponseHelper::success('Stock out retrieved successfully', $stockOut);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'customer_id' => 'nullable|exists:customers,id',
                'quantity' => 'required|integer|min:1',
                'sold_date' => 'required|date',
                'remarks' => 'nullable|string'
            ]);

            // OPTIMIZED: Check stock and create in optimized way
            $product = Product::findOrFail($validated['product_id']);

            if ($product->stock_quantity < $validated['quantity']) {
                return ResponseHelper::error('Insufficient stock', null, 422);
            }

            $stockOut = StockOut::create($validated);

            // OPTIMIZED: Use decrement for atomic update
            Product::where('id', $validated['product_id'])
                ->decrement('stock_quantity', $validated['quantity']);

            ActivityLogHelper::log('stock_out', "Stock Out: {$validated['quantity']} units of {$product->name}");

            // Clear caches
            Cache::forget('stock_out_dashboard');
            Cache::forget('stock_report');

            return ResponseHelper::success('Stock out created successfully', $stockOut, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $stockOut = StockOut::findOrFail($id);

            $validated = $request->validate([
                'product_id' => 'sometimes|required|exists:products,id',
                'customer_id' => 'nullable|exists:customers,id',
                'quantity' => 'sometimes|required|integer|min:1',
                'sold_date' => 'sometimes|required|date',
                'remarks' => 'nullable|string'
            ]);

            $stockOut->update($validated);
            
            // Clear caches
            Cache::forget('stock_out_dashboard');

            return ResponseHelper::success('Stock out updated successfully', $stockOut);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $stockOut = StockOut::findOrFail($id);
            $stockOut->delete();
            
            // Clear caches
            Cache::forget('stock_out_dashboard');
            
            return ResponseHelper::success('Stock out deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * FIXED: added column selection for product and customer relationships
     * Get dashboard data
     */
    public function dashboardData()
    {
        try {
            // OPTIMIZED: Cache dashboard for 5 minutes
            $data = Cache::remember('stock_out_dashboard', 300, function () {
                $totalStockOut = StockOut::sum('quantity');
                $stockOuts = StockOut::select('id', 'product_id', 'customer_id', 'quantity', 'sold_date', 'remarks', 'created_at')
                    ->with(['product:id,name,sku,price', 'customer:id,name,phone,email'])
                    ->latest()
                    ->take(10)
                    ->get();
                
                return [
                    'total_stock_out' => $totalStockOut,
                    'recent_stock_outs' => $stockOuts
                ];
            });
            
            return ResponseHelper::success('Stock out dashboard data retrieved successfully', $data);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * FIXED: added column selection for product and customer relationships
     * Get receipt
     */
    public function receipt($id)
    {
        try {
            $stockOut = StockOut::with(['product:id,name,sku,price', 'customer:id,name,phone,email'])->findOrFail($id);
            return ResponseHelper::success('Receipt retrieved successfully', $stockOut);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
