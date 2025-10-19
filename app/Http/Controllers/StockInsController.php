<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock_ins as StockIn;
use App\Models\Products as Product;
use Illuminate\Support\Facades\DB;

class StockInsController extends Controller
{


    //stockin total and total money
    public function totalStockIn(){
    try {
        // Total quantity of stock-in
        $totalQuantity = StockIn::sum('quantity');

        // Total money for stock-in (quantity * price)
        // Assuming your Stock_ins table has a 'price' column
        $totalMoney = StockIn::sum(DB::raw('quantity * unit_cost'));

        return response()->json([
            'status' => 200,
            'message' => 'Stock-in summary retrieved successfully',
            'total_quantity' => $totalQuantity,
            'total_money' => $totalMoney
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ]);
    }
}
    public function index()
    {
        try {
            $stockIns = StockIn::all();
            return response()->json(['status'=>200,'message'=>'Stock ins retrieved successfully','data'=>$stockIns],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

public function store(Request $request)
{
    try {
        // Validate request (without total_cost)
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'unit_cost' => 'required|numeric',

            'received_by' => 'required|exists:users,id',
            'remarks' => 'nullable|string'
        ]);

        // Calculate total_cost automatically
        $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];

        // Create StockIn record
        $stockIn = StockIn::create($validated);

        // Update product stock
        $product = Product::find($validated['product_id']);
        $product->stock_quantity += $validated['quantity'];
        $product->save();

        return response()->json([
            'status' => 201,
            'message' => 'Stock in recorded successfully',
            'data' => $stockIn
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function update(Request $request,$id)
    {
        try {
            $stockIn = StockIn::findOrFail($id);

            $validated = $request->validate([
                'supplier_id'=>'sometimes|required|exists:suppliers,id',
                'product_id'=>'sometimes|required|exists:products,id',
                'quantity'=>'sometimes|required|integer',
                'unit_cost'=>'sometimes|required|numeric',
                'total_cost'=>'sometimes|required|numeric',
                'received_date'=>'sometimes|required|date',
                'received_by'=>'sometimes|required|exists:users,id',
                'remarks'=>'nullable|string'
            ]);

            // Optional: Adjust stock if quantity changes
            if(isset($validated['quantity'])){
                $product = Product::find($stockIn->product_id);
                $product->stock_quantity -= $stockIn->quantity; // remove old
                $product->stock_quantity += $validated['quantity']; // add new
                $product->save();
            }

            $stockIn->update($validated);
            return response()->json(['status'=>200,'message'=>'Stock in updated successfully','data'=>$stockIn],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $stockIn = StockIn::findOrFail($id);

            // Reduce product stock
            $product = Product::find($stockIn->product_id);
            $product->stock_quantity -= $stockIn->quantity;
            $product->save();

            $stockIn->delete();
            return response()->json(['status'=>200,'message'=>'Stock in deleted successfully'],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
