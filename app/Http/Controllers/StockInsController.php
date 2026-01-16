<?php

namespace App\Http\Controllers;

use App\Models\Activity_logs;
use Illuminate\Http\Request;
use App\Models\StockIn;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ActivityLog;
use App\Models\Products;
use App\Models\Stock_ins;
use App\Models\Suppliers;
use Illuminate\Support\Facades\DB;

class StockInsController extends Controller
{
    /**
     * Get all necessary data for Stock In Page in one request
     */
    public function overview()
    {
        try {
            // Eager load relationships and flatten for frontend
            $stockIns = Stock_ins::with(['product', 'supplier', 'receivedBy'])
                ->orderByDesc('id')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'stock_in_code' => $item->stock_in_code,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? '',
                        'supplier_id' => $item->supplier_id,
                        'supplier_name' => $item->supplier->name ?? '',
                        'quantity' => $item->quantity,
                        'unit_cost' => (float) $item->unit_cost,
                        'total_cost' => (float) $item->total_cost,
                        'received_date' => $item->date,
                        'received_by_id' => $item->received_by,
                        'received_by_name' => $item->receivedBy->name ?? '',
                        'remarks' => $item->remarks ?? '',
                    ];
                });

            $suppliers = Suppliers::select('id', 'name')->get();
            $products  = Products::select('id', 'name', 'stock_quantity')->get();
            $users     = DB::table('users')->select('id', 'name')->get();

            return response()->json([
                'status' => 200,
                'message' => 'Stock In overview loaded successfully',
                'stock_history' => $stockIns,
                'suppliers' => $suppliers,
                'products' => $products,
                'users' => $users
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
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

            // Calculate total cost
            $validated['stock_in_code'] = 'SI-' . substr(time(), -5);
            $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];

            $stockIn = Stock_ins::create($validated);

            // Update product stock
            $product = Products::find($validated['product_id']);
            $product->stock_quantity += $validated['quantity'];
            $product->save();

            // Log activity
            Activity_logs::create([
                'user_id' => auth()->id() ?? $validated['received_by'],
                'action' => 'created',
                'module' => 'stock_ins',
                'record_id' => $stockIn->id
            ]);

            // Return flattened JSON for frontend
            return response()->json([
                'status' => 201,
                'message' => 'Stock In recorded successfully',
                'data' => [
                    'id' => $stockIn->id,
                    'stock_in_code' => $stockIn->stock_in_code,
                    'product' => $product->name,
                    'supplier' => $stockIn->supplier->name ?? '',
                    'quantity' => $stockIn->quantity,
                    'unit_cost' => (float) $stockIn->unit_cost,
                    'total_cost' => (float) $stockIn->total_cost,
                    'received_date' => $stockIn->date,
                    'received_by' => $stockIn->receivedBy->name ?? '',
                    'remarks' => $stockIn->remarks ?? ''
                ]
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock-in record
     */
    public function update(Request $request, $id)
    {
        try {
            $stockIn = Stock_ins::findOrFail($id);

            $validated = $request->validate([
                'supplier_id' => 'sometimes|required|exists:suppliers,id',
                'product_id' => 'sometimes|required|exists:products,id',
                'quantity' => 'sometimes|required|integer|min:1',
                'unit_cost' => 'sometimes|required|numeric|min:0',
                'date' => 'sometimes|required|date',
                'received_by' => 'sometimes|required|exists:users,id',
                'remarks' => 'nullable|string'
            ]);

            // Adjust product stock if quantity changes
            if (isset($validated['quantity'])) {
                $product = Products::find($stockIn->product_id);
                $product->stock_quantity -= $stockIn->quantity;
                $product->stock_quantity += $validated['quantity'];
                $product->save();
            }

            $stockIn->update($validated);

            Activity_logs::create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'module' => 'stock_ins',
                'record_id' => $stockIn->id
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Stock In updated successfully',
                'data' => $stockIn
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete stock-in record
     */
    public function destroy($id)
    {
        try {
            $stockIn = Stock_ins::findOrFail($id);

            $product = Products::find($stockIn->product_id);
            $product->stock_quantity -= $stockIn->quantity;
            $product->save();

            $stockIn->delete();

            Activity_logs::create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'module' => 'stock_ins',
                'record_id' => $stockIn->id
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Stock In deleted successfully'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
