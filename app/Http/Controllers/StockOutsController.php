<?php

namespace App\Http\Controllers;

use App\Models\Stock_outs as StockOut;
use App\Models\Products as Product;
use App\Models\Activity_logs as ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StockOutsController extends Controller
{
    /**
     * List all stock-out records
     */
    public function index()
    {
        return Cache::remember('stock_outs_list', 5, function () {
            $stockOuts = StockOut::with(['product','customer','soldBy'])
                ->orderBy('sold_date','asc')
                ->get();

            return response()->json($stockOuts->map(function($sale){
                return [
                    'id' => $sale->id,
                    'customer_name' => $sale->customer->name ?? '',
                    'product_name' => $sale->product->name ?? '',
                    'quantity' => $sale->quantity,
                    'unit_price' => $sale->unit_price,
                    'total_amount' => $sale->total_amount,
                    'sold_date' => $sale->sold_date,
                    'sold_by' => $sale->soldBy->name ?? '',
                    'remarks' => $sale->remarks
                ];
            }), 200);
        });
    }

    /**
     * Store a new stock-out record
     */

    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'customer_id'=>'required|exists:customers,id',
            'product_id'=>'required|exists:products,id',
            'quantity'=>'required|integer|min:1',
            'unit_price'=>'required|numeric|min:0',
            'total_amount'=>'required|numeric|min:0',
            'sold_date'=>'required|date',
            'sold_by'=>'required|exists:users,id',
            'remarks'=>'nullable|string',
        ]);

        // Create stock-out record
        $stockOut = StockOut::create($validated);

        // Decrease product stock
        $product = Product::findOrFail($validated['product_id']);
        $product->stock_quantity -= $validated['quantity'];
        $product->save();

        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'created', 'module' => 'stock_outs', 'record_id' => $stockOut->id]);

        // Load relationships for proper names
        $stockOut->load(['customer', 'product', 'soldBy']);

        // Flattened response
        $responseData = [
            'id' => $stockOut->id,
            'customer_name' => $stockOut->customer->name ?? '',
            'product_name' => $stockOut->product->name ?? '',
            'quantity' => $stockOut->quantity,
            'unit_price' => $stockOut->unit_price,
            'total_amount' => $stockOut->total_amount,
            'sold_date' => $stockOut->sold_date,
            'sold_by' => $stockOut->soldBy->name ?? '',
            'remarks' => $stockOut->remarks
        ];

        return response()->json($responseData, 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ]);
    }
}


    /**
     * Update a stock-out record
     */
    public function update(Request $request, $id)
    {
        $stockOut = StockOut::findOrFail($id);

        $validated = $request->validate([
            'customer_id'=>'sometimes|required|exists:customers,id',
            'product_id'=>'sometimes|required|exists:products,id',
            'quantity'=>'sometimes|required|integer|min:1',
            'unit_price'=>'sometimes|required|numeric|min:0',
            'total_amount'=>'sometimes|required|numeric|min:0',
            'sold_date'=>'sometimes|required|date',
            'sold_by'=>'sometimes|required|exists:users,id',
            'remarks'=>'nullable|string',
        ]);

        // Adjust stock if quantity changed
        if(isset($validated['quantity']) && $validated['quantity'] != $stockOut->quantity){
            $product = Product::find($stockOut->product_id);
            $product->stock_quantity -= $stockOut->quantity; // remove old quantity
            $product->stock_quantity += $validated['quantity']; // add new quantity
            $product->save();
        }

        $stockOut->update($validated);

        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'updated', 'module' => 'stock_outs', 'record_id' => $stockOut->id]);

        return response()->json([
            'id' => $stockOut->id,
            'customer_name' => $stockOut->customer->name ?? '',
            'product_name' => $stockOut->product->name ?? '',
            'quantity' => $stockOut->quantity,
            'unit_price' => $stockOut->unit_price,
            'total_amount' => $stockOut->total_amount,
            'sold_date' => $stockOut->sold_date,
            'sold_by' => $stockOut->soldBy->name ?? '',
            'remarks' => $stockOut->remarks
        ], 200);
    }

    /**
     * Delete a stock-out record
     */
    public function destroy($id)
    {
        $stockOut = StockOut::findOrFail($id);

        // Restore product stock
        $product = Product::find($stockOut->product_id);
        $product->stock_quantity += $stockOut->quantity;
        $product->save();

        $stockOut->delete();

        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'deleted', 'module' => 'stock_outs', 'record_id' => $stockOut->id]);

        return response()->json(['message'=>'Stock-out deleted successfully'],200);
    }

    /**
     * Generate a simple receipt/summary
     */
    public function receipt($id)
    {
        return Cache::remember("stock_out_receipt_{$id}", 5, function () use ($id) {
            $sale = StockOut::with(['product','customer','soldBy'])->findOrFail($id);

            return response()->json([
                'invoice_id' => "INV-".$sale->id,
                'customer' => $sale->customer->name ?? '',
                'product' => $sale->product->name ?? '',
                'quantity' => $sale->quantity,
                'unit_price' => $sale->unit_price,
                'total_amount' => $sale->total_amount,
                'sold_by' => $sale->soldBy->name ?? '',
                'date' => $sale->sold_date,
                'remarks' => $sale->remarks
            ]);
        });
    }
}
