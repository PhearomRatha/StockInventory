<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Products as Product;

class ProductController extends Controller
{

// Count total products
public function totalPro()
{
    try {
        $total = Product::count();

        return response()->json([
            'status' => 200,
            'message' => 'Total products retrieved successfully',
            'total_products' => $total
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ]);
    }
}

// Check stock status: low stock / out of stock
public function stock()
{
    try {
        $products = Product::all();
        $lowStock = [];
        $outOfStock = [];

        foreach ($products as $product) {
            // Out of stock
            if ($product->stock_quantity == 0) {
                $outOfStock[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock_quantity' => $product->stock_quantity,
                    'status' => 'Out of Stock'
                ];
            }
            // Low stock (30% of reorder_level)
            elseif ($product->reorder_level && $product->stock_quantity <= ($product->reorder_level * 0.3)) {
                $lowStock[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock_quantity' => $product->stock_quantity,
                    'status' => 'Low Stock'
                ];
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Stock status retrieved successfully',
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock
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
        $products = Product::with(['category', 'supplier'])->get(); // eager load relationships

        $productsTable = $products->map(function ($product) {
            // Determine status
            if ($product->stock_quantity == 0) {
                $status = 'Out of Stock';
            } elseif ($product->reorder_level > 0 && $product->stock_quantity <= $product->reorder_level) {
                $status = 'Low Stock';
            } else {
                $status = 'In Stock';
            }

            return [
                'id' => $product->id,
                'product' => $product->name,
                'category' => $product->category ? $product->category->name : null,
                'supplier' => $product->supplier ? $product->supplier->name : null,
                'price' => $product->price,
                'stock' => $product->stock_quantity,
                'status' => $status,

            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'All products retrieved successfully',
            'data' => $productsTable
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ]);
    }
}



    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'sku' => 'required|unique:products,sku',
                'barcode' => 'nullable|string',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'cost' => 'required|numeric',
                'stock_quantity' => 'required|integer',
                'reorder_level' => 'nullable|integer',
                'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);
if ($request->hasFile('image')) {
    $path = $request->file('image')->store('products', 'public');
    $validated['image'] = asset('storage/' . $path);
}


            $product = Product::create($validated);
            return response()->json([
                'status' => 201,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

        // use put
    public function update(Request $request, $id)
{
    try {



        // Validate input
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'sku' => 'sometimes|required|unique:products,sku,' . $id,
            'barcode' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric',
            'cost' => 'sometimes|required|numeric',
            'stock_quantity' => 'sometimes|required|integer',
            'reorder_level' => 'nullable|integer',
            'image' => 'nullable', // accept string URL or file
        ]);

        // Find product
        $product = Product::findOrFail($id);

        // Handle uploaded image file
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }

        // Handle image URL (string)
        if ($request->filled('image') && !$request->hasFile('image')) {
            $validated['image'] = $request->input('image');
        }

        // Update product
        $product->update($validated);

        // Optional: return full public URL for image
        if (!empty($product->image)) {
            $product->image = url('storage/' . $product->image);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ]);
    }


}









    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Product deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
