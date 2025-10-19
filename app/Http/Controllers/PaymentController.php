<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payments as Payment;
use App\Models\Sales as Sale;
use App\Models\Stock_ins as StockIn;

class PaymentController extends Controller
{
    public function index()
    {
        try {
            $payments = Payment::all();
            return response()->json(['status'=>200,'message'=>'Payments retrieved successfully','data'=>$payments],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'reference_type'=>'required|in:sale,purchase',
                'reference_id'=>'required|integer',
                'amount'=>'required|numeric',
                'payment_type'=>'required|in:income,expense',
                'payment_method'=>'nullable|string',
                'paid_to_from'=>'required|string',
                'payment_date'=>'required|date',
                'recorded_by'=>'required|exists:users,id'
            ]);

            // Check reference exists
            if($validated['reference_type']=='sale' && !Sale::find($validated['reference_id'])){
                return response()->json(['status'=>404,'message'=>'Sale not found'],404);
            }
            if($validated['reference_type']=='purchase' && !StockIn::find($validated['reference_id'])){
                return response()->json(['status'=>404,'message'=>'Stock in not found'],404);
            }

            $payment = Payment::create($validated);
            return response()->json(['status'=>201,'message'=>'Payment recorded successfully','data'=>$payment],201);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function update(Request $request,$id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $validated = $request->validate([
                'reference_type'=>'sometimes|required|in:sale,purchase',
                'reference_id'=>'sometimes|required|integer',
                'amount'=>'sometimes|required|numeric',
                'payment_type'=>'sometimes|required|in:income,expense',
                'payment_method'=>'nullable|string',
                'paid_to_from'=>'sometimes|required|string',
                'payment_date'=>'sometimes|required|date',
                'recorded_by'=>'sometimes|required|exists:users,id'
            ]);

            $payment->update($validated);
            return response()->json(['status'=>200,'message'=>'Payment updated successfully','data'=>$payment],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment->delete();
            return response()->json(['status'=>200,'message'=>'Payment deleted successfully'],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
