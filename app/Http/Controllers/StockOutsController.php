<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock_outs as StockOut;
use App\Models\Products as Product;
use App\Helpers\ResponseHelper;
use App\Helpers\ActivityLogHelper;

class StockOutsController extends Controller
{
    public function index()
    {
        try {
            $stockOuts = StockOut::with(['product', 'customer'])->get();
            return ResponseHelper::success('Stock outs retrieved successfully', $stockOuts);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $stockOut = StockOut::with(['product', 'customer'])->findOrFail($id);
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
                'date' => 'required|date',
                'notes' => 'nullable|string'
            ]);

            $product = Product::findOrFail($validated['product_id']);

            if ($product->stock_quantity < $validated['quantity']) {
                return ResponseHelper::error('Insufficient stock', null, 422);
            }

            $stockOut = StockOut::create($validated);

            $product->stock_quantity -= $validated['quantity'];
            $product->save();

            ActivityLogHelper::log('stock_out', "Stock Out: {$validated['quantity']} units of {$product->name}");

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
                'date' => 'sometimes|required|date',
                'notes' => 'nullable|string'
            ]);

            $stockOut->update($validated);
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
            return ResponseHelper::success('Stock out deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function dashboardData()
    {
        try {
            $totalStockOut = StockOut::sum('quantity');
            $stockOuts = StockOut::with(['product', 'customer'])->latest()->take(10)->get();
            return ResponseHelper::success('Stock out dashboard data retrieved successfully', [
                'total_stock_out' => $totalStockOut,
                'recent_stock_outs' => $stockOuts
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function receipt($id)
    {
        try {
            $stockOut = StockOut::with(['product', 'customer'])->findOrFail($id);
            return ResponseHelper::success('Receipt retrieved successfully', $stockOut);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
