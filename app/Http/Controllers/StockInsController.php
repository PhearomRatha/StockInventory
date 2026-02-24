<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock_ins as StockIn;
use App\Helpers\ResponseHelper;
use App\Helpers\ActivityLogHelper;

class StockInsController extends Controller
{
    public function index()
    {
        try {
            $stockIns = StockIn::with(['product', 'supplier'])->get();
            return ResponseHelper::success('Stock ins retrieved successfully', $stockIns);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function overview()
    {
        try {
            $totalStockIn = StockIn::sum('quantity');
            $stockIns = StockIn::with(['product', 'supplier'])->latest()->take(10)->get();
            return ResponseHelper::success('Stock in overview retrieved successfully', [
                'total_stock_in' => $totalStockIn,
                'recent_stock_ins' => $stockIns
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'quantity' => 'required|integer|min:1',
                'date' => 'required|date',
                'notes' => 'nullable|string'
            ]);

            $stockIn = StockIn::create($validated);

            // Update product stock
            $stockIn->product->stock_quantity += $validated['quantity'];
            $stockIn->product->save();

            ActivityLogHelper::log('stock_in', "Stock In: {$validated['quantity']} units of {$stockIn->product->name} from {$stockIn->supplier->name}");

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
                'notes' => 'nullable|string'
            ]);

            $stockIn->update($validated);
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
            return ResponseHelper::success('Stock in deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function totalStockIn()
    {
        try {
            $total = StockIn::sum('quantity');
            return ResponseHelper::success('Total stock in retrieved successfully', ['total_stock_in' => $total]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
