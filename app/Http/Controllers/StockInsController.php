<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock_ins as StockIn;
use App\Models\Products as Product;
use App\Models\Suppliers as Supplier;
use App\Models\Activity_logs as ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StockInsController extends Controller
{
    /**
     * Get total stock-in quantity and total money
     */
    public function totalStockIn()
    {
        try {
            return Cache::remember('stock_ins_total', 10, function () {
                $totalQuantity = StockIn::sum('quantity');
                $totalMoney = StockIn::sum(DB::raw('quantity * unit_cost'));

                return response()->json([
                    'status' => 200,
                    'message' => 'Stock-in summary retrieved successfully',
                    'total_quantity' => $totalQuantity,
                    'total_money' => $totalMoney
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * List all stock-in records with optional filters
     */
    public function index(Request $request)
{
    try {
        $cacheKey = 'stock_ins_list_' . md5(serialize($request->all()));
        return Cache::remember($cacheKey, 5, function () use ($request) {
            // Eager load product, supplier, and receivedBy relationships
            $query = StockIn::with(['product', 'supplier', 'receivedBy']);

            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            if ($request->has('received_date')) {
                $query->whereDate('received_date', $request->received_date);
            }

            $stockIns = $query->latest('received_date')->get();

            return response()->json([
                'status' => 200,
                'message' => 'Stock In records fetched successfully',
                'data' => $stockIns->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'stock_in_code' => $item->stock_in_code,
                        'product' => $item->product->name ?? '',
                        'supplier' => $item->supplier->name ?? '',
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->unit_cost,
                        'total_cost' => $item->total_cost,
                        'received_date' => $item->received_date,
                        'received_by' => $item->receivedBy->name ?? '', // use the relationship
                    ];
                }),
            ]);
        });
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage(),
        ]);
    }
}

    /**
     * Store new stock-in record
     */
    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'date' => 'required|date',
            'received_by' => 'required|exists:users,id',
            'remarks' => 'nullable|string'
        ]);

        // Add auto ID and total
        $validated['stock_in_code'] = 'SI-' . substr(time(), -5);
        $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];

        // Create StockIn record
        $stockIn = StockIn::create($validated);

        // Update product stock quantity
        $product = Product::find($validated['product_id']);
        $product->stock_quantity += $validated['quantity'];
        $product->save();

        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'created', 'module' => 'stock_ins', 'record_id' => $stockIn->id]);

        // Prepare flattened data for frontend
        $responseData = [
            'id' => $stockIn->id,
            'stock_in_code' => $stockIn->stock_in_code,
            'product' => $product->name,
            'supplier' => $stockIn->supplier->name ?? '',
            'quantity' => $stockIn->quantity,
            'unit_cost' => $stockIn->unit_cost,
            'total_cost' => $stockIn->total_cost,
            'received_date' => $stockIn->date,
            'remarks' => $stockIn->remarks,
        ];

        return response()->json([
            'status' => 201,
            'message' => 'Stock in recorded successfully',
            'data' => $responseData
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Update existing stock-in record
     */
    public function update(Request $request, $id)
    {
        try {
            $stockIn = StockIn::findOrFail($id);

            $validated = $request->validate([
                'supplier_id' => 'sometimes|required|exists:suppliers,id',
                'product_id' => 'sometimes|required|exists:products,id',
                'quantity' => 'sometimes|required|integer|min:1',
                'unit_cost' => 'sometimes|required|numeric|min:0',
                'total_cost' => 'sometimes|numeric|min:0',
                'date' => 'sometimes|required|date',
                'received_by' => 'sometimes|required|exists:users,id',
                'remarks' => 'nullable|string'
            ]);

            // Adjust stock if quantity changes
            if (isset($validated['quantity'])) {
                $product = Product::find($stockIn->product_id);
                $product->stock_quantity -= $stockIn->quantity; // remove old
                $product->stock_quantity += $validated['quantity']; // add new
                $product->save();
            }

            // Update stock in record
            $stockIn->update($validated);

            ActivityLog::create(['user_id' => auth()->id(), 'action' => 'updated', 'module' => 'stock_ins', 'record_id' => $stockIn->id]);

            return response()->json([
                'status' => 200,
                'message' => 'Stock in updated successfully',
                'data' => $stockIn
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete stock-in record
     */
    public function destroy($id)
    {
        try {
            $stockIn = StockIn::findOrFail($id);

            // Decrease product stock
            $product = Product::find($stockIn->product_id);
            $product->stock_quantity -= $stockIn->quantity;
            $product->save();

            $stockIn->delete();

            ActivityLog::create(['user_id' => auth()->id(), 'action' => 'deleted', 'module' => 'stock_ins', 'record_id' => $stockIn->id]);

            return response()->json([
                'status' => 200,
                'message' => 'Stock in deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
}
