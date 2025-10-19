<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suppliers as Supplier;

class SuppliersController extends Controller
{
    public function index()
    {
        try {
            $suppliers = Supplier::all();
            return response()->json(['status'=>200,'message'=>'Suppliers retrieved successfully','data'=>$suppliers],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'=>'required|string|max:255',
                'company'=>'nullable|string|max:255',
                'phone'=>'nullable|string|max:20',
                'email'=>'nullable|email',
                'address'=>'nullable|string',
                'notes'=>'nullable|string'
            ]);

            $supplier = Supplier::create($validated);
            return response()->json(['status'=>201,'message'=>'Supplier created successfully','data'=>$supplier],201);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function update(Request $request,$id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $validated = $request->validate([
                'name'=>'sometimes|required|string|max:255',
                'company'=>'nullable|string|max:255',
                'phone'=>'nullable|string|max:20',
                'email'=>'nullable|email',
                'address'=>'nullable|string',
                'notes'=>'nullable|string'
            ]);

            $supplier->update($validated);
            return response()->json(['status'=>200,'message'=>'Supplier updated successfully','data'=>$supplier],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();
            return response()->json(['status'=>200,'message'=>'Supplier deleted successfully'],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
