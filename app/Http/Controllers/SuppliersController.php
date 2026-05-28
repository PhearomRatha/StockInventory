<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Suppliers as Supplier;
use Illuminate\Http\Request;

class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        try {
            $perPage = $request->query('per_page', 15);

            // OPTIMIZED: Add pagination and select columns
            $suppliers = Supplier::select('id', 'name', 'email', 'phone', 'address')
                ->paginate(min($perPage, 100));

            return ResponseHelper::success('Suppliers retrieved successfully', $suppliers);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreSupplierRequest $request)
    {
        $this->authorize('create', Supplier::class);

        try {
            $validated = $request->validated();

            $supplier = Supplier::create($validated);

            return ResponseHelper::success('Supplier created successfully', $supplier, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(StoreSupplierRequest $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $this->authorize('update', $supplier);
            $validated = $request->validated();

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
            $this->authorize('delete', $supplier);
            $supplier->delete();

            return ResponseHelper::success('Supplier deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
