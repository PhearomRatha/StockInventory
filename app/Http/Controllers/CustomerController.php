<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customers as Customer;
use App\Helpers\ResponseHelper;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            
            // OPTIMIZED: Add pagination and select only needed columns
            $customers = Customer::select('id', 'name', 'email', 'phone', 'address', 'type')
                ->paginate(min($perPage, 100));
            
            return ResponseHelper::success('Customers retrieved successfully', $customers);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            return ResponseHelper::success('Customer retrieved successfully', $customer);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string'
            ]);

            $customer = Customer::create($validated);
            return ResponseHelper::success('Customer created successfully', $customer, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string'
            ]);

            $customer->update($validated);
            return ResponseHelper::success('Customer updated successfully', $customer);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();
            return ResponseHelper::success('Customer deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
