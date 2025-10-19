<?php

namespace App\Http\Controllers;

use App\Models\Stock_outs as StockOut;
use App\Models\Products as Product;
use Illuminate\Http\Request;

class StockOutsController extends Controller
{
    public function index() { return response()->json(StockOut::all(), 200); }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'=>'required|exists:customers,id',
            'product_id'=>'required|exists:products,id',
            'quantity'=>'required|integer',
            'unit_price'=>'required|numeric',
            'total_amount'=>'required|numeric',
            'sold_date'=>'required|date',
            'sold_by'=>'required|exists:users,id',
            'remarks'=>'nullable|string',
        ]);

        $stockOut = StockOut::create($request->all());

        // Update product stock
        $product = Product::find($request->product_id);
        $product->stock_quantity -= $request->quantity;
        $product->save();

        return response()->json($stockOut, 201);
    }

    public function update(Request $request,$id)
    {
        $stockOut = StockOut::findOrFail($id);
        $request->validate([
            'customer_id'=>'sometimes|required|exists:customers,id',
            'product_id'=>'sometimes|required|exists:products,id',
            'quantity'=>'sometimes|required|integer',
            'unit_price'=>'sometimes|required|numeric',
            'total_amount'=>'sometimes|required|numeric',
            'sold_date'=>'sometimes|required|date',
            'sold_by'=>'sometimes|required|exists:users,id',
            'remarks'=>'nullable|string',
        ]);
        $stockOut->update($request->all());
        return response()->json($stockOut, 200);
    }

    public function destroy($id)
    {
        $stockOut = StockOut::findOrFail($id);

        // Optional: Restore stock when deleting
        $product = Product::find($stockOut->product_id);
        $product->stock_quantity += $stockOut->quantity;
        $product->save();

        $stockOut->delete();
        return response()->json(['message'=>'Stock-out deleted successfully'],200);
    }
}
