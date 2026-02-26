<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suppliers as Supplier;
use App\Helpers\ResponseHelper;

class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            
            // OPTIMIZED: Add pagination and select columns
            $suppliers = Supplier::select('id', 'name', 'contact_person', 'email', 'phone', 'address')
                ->paginate(min($perPage, 100));
            
            return ResponseHelper::success('Suppliers retrieved successfully', $suppliers);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string'
            ]);

            $supplier = Supplier::create($validated);
            return ResponseHelper::success('Supplier created successfully', $supplier, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string'
            ]);

            $supplier->update($validated);
            return ResponseHelper::success('Supplier updated successfully', $supplier);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();
            return ResponseHelper::success('Supplier deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
