<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\customers as Customer;

class CustomerController extends Controller
{


    public function index()
    {
        try {
            $customers = Customer::all();
            return response()->json(['status'=>200,'message'=>'Customers retrieved successfully','data'=>$customers],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'=>'required|string|max:255',
                'email'=>'nullable|email|unique:customers,email',
                'phone'=>'nullable|string|max:20',
                'address'=>'nullable|string',
                'preferences'=>'nullable|string',
                'notes'=>'nullable|string'
            ]);

            $customer = Customer::create($validated);
            return response()->json(['status'=>201,'message'=>'Customer created successfully','data'=>$customer],201);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function update(Request $request,$id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $validated = $request->validate([
                'name'=>'sometimes|required|string|max:255',
                'email'=>'nullable|email|unique:customers,email,'.$id,
                'phone'=>'nullable|string|max:20',
                'address'=>'nullable|string',
                'preferences'=>'nullable|string',
                'notes'=>'nullable|string'
            ]);

            $customer->update($validated);
            return response()->json(['status'=>200,'message'=>'Customer updated successfully','data'=>$customer],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();
            return response()->json(['status'=>200,'message'=>'Customer deleted successfully'],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
