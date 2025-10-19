<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sales as Sale;

class SalesController extends Controller
{
    public function index()
    {
        try {
            $sales = Sale::all();
            return response()->json(['status'=>200,'message'=>'Sales retrieved successfully','data'=>$sales],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id'=>'required|exists:customers,id',
                'invoice_number'=>'required|string|unique:sales,invoice_number',
                'total_amount'=>'required|numeric',
                'discount'=>'nullable|numeric',
                'payment_status'=>'required|in:paid,unpaid,partial',
                'payment_method'=>'nullable|string',
                'sold_by'=>'required|exists:users,id'
            ]);

            $sale = Sale::create($validated);
            return response()->json(['status'=>201,'message'=>'Sale created successfully','data'=>$sale],201);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function update(Request $request,$id)
    {
        try {
            $sale = Sale::findOrFail($id);

            $validated = $request->validate([
                'customer_id'=>'sometimes|required|exists:customers,id',
                'invoice_number'=>'sometimes|required|string|unique:sales,invoice_number,'.$id,
                'total_amount'=>'sometimes|required|numeric',
                'discount'=>'nullable|numeric',
                'payment_status'=>'sometimes|required|in:paid,unpaid,partial',
                'payment_method'=>'nullable|string',
                'sold_by'=>'sometimes|required|exists:users,id'
            ]);

            $sale->update($validated);
            return response()->json(['status'=>200,'message'=>'Sale updated successfully','data'=>$sale],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $sale = Sale::findOrFail($id);
            $sale->delete();
            return response()->json(['status'=>200,'message'=>'Sale deleted successfully'],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
