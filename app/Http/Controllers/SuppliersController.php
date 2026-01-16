<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suppliers as Supplier;
use App\Models\Activity_logs as ActivityLog;

class SuppliersController extends Controller
{
    public function index()
    {
        try {
            $suppliers = Supplier::all();
            return response()->json([
                'status' => 200,
                'message' => 'Suppliers retrieved successfully',
                'data' => $suppliers
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate only fields that exist in DB
  $validated = $request->validate([
    'name'    => 'required|string|max:255',
    'contact' => 'nullable|string|max:255',
    'address' => 'nullable|string',
    'company' => 'nullable|string|max:255',
    'phone'   => 'nullable|string|max:20',
    'email'   => 'nullable|email'
]);


            $supplier = Supplier::create($validated);

            ActivityLog::create([
                'user_id'   => auth()->id(),
                'action'    => 'created',
                'module'    => 'suppliers',
                'record_id' => $supplier->id
            ]);

            return response()->json([
                'status'  => 201,
                'message' => 'Supplier created successfully',
                'data'    => $supplier
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status'=>500, 'message'=>$e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

          $validated = $request->validate([
    'name'    => 'required|string|max:255',
    'contact' => 'nullable|string|max:255',
    'address' => 'nullable|string',
    'company' => 'nullable|string|max:255',
    'phone'   => 'nullable|string|max:20',
    'email'   => 'nullable|email'
]);


            $supplier->update($validated);

            ActivityLog::create([
                'user_id'   => auth()->id(),
                'action'    => 'updated',
                'module'    => 'suppliers',
                'record_id' => $supplier->id
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Supplier updated successfully',
                'data'    => $supplier
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status'=>500, 'message'=>$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            ActivityLog::create([
                'user_id'   => auth()->id(),
                'action'    => 'deleted',
                'module'    => 'suppliers',
                'record_id' => $supplier->id
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Supplier deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status'=>500, 'message'=>$e->getMessage()], 500);
        }
    }
}
