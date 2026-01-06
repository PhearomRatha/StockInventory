<?php
namespace App\Http\Controllers;

use App\Models\Products as Product;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    // Count total products
    public function totalPro()
    {
        try {
            $total = Product::count();

            return response()->json([
                'status'         => 200,
                'message'        => 'Total products retrieved successfully',
                'total_products' => $total,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Check stock status: low stock / out of stock
    public function stock()
    {
        try {
            $products   = Product::all();
            $lowStock   = [];
            $outOfStock = [];

            foreach ($products as $product) {
                // Out of stock
                if ($product->stock_quantity == 0) {
                    $outOfStock[] = [
                        'id'             => $product->id,
                        'name'           => $product->name,
                        'stock_quantity' => $product->stock_quantity,
                        'status'         => 'Out of Stock',
                    ];
                }
                // Low stock (30% of reorder_level)
                elseif ($product->reorder_level && $product->stock_quantity <= ($product->reorder_level * 0.3)) {
                    $lowStock[] = [
                        'id'             => $product->id,
                        'name'           => $product->name,
                        'stock_quantity' => $product->stock_quantity,
                        'status'         => 'Low Stock',
                    ];
                }
            }

            return response()->json([
                'status'       => 200,
                'message'      => 'Stock status retrieved successfully',
                'low_stock'    => $lowStock,
                'out_of_stock' => $outOfStock,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // List all products
    public function index()
    {
        try {
            $products = Product::with(['category', 'supplier'])->get();

            $productsTable = $products->map(function ($product) {
                $status = $product->stock_quantity == 0 ? 'Out of Stock'
                    : ($product->reorder_level > 0 && $product->stock_quantity <= $product->reorder_level ? 'Low Stock'
                        : 'In Stock');

                return [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'barcode'        => $product->barcode,
                    'category'       => $product->category?->name ?? 'N/A',
                    'supplier'       => $product->supplier?->name ?? 'N/A',
                    'price'          => $product->price,
                    'cost'           => $product->cost,
                    'reorder_level'  => $product->reorder_level,
                    'description'    => $product->description,
                    'stock_quantity' => $product->stock_quantity,
                    'status'         => $status,
                    'image'          => $product->image
                        ? (str_starts_with($product->image, 'http') ? $product->image : url('storage/' . $product->image))
                        : null,
                ];
            });

            return response()->json([
                'status'  => 200,
                'message' => 'All products retrieved successfully',
                'data'    => $productsTable,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching products: ' . $e->getMessage(),
            ]);
        }
    }
    // List all products
    public function show($id)
    {
        try {
            $product = Product::with(['category', 'supplier'])->findOrFail($id);

            $status = $product->stock_quantity == 0 ? 'Out of Stock'
                : ($product->reorder_level > 0 && $product->stock_quantity <= $product->reorder_level ? 'Low Stock'
                    : 'In Stock');

            $productData = [
                'id'             => $product->id,
                'name'           => $product->name,
                'sku'            => $product->sku,
                'barcode'        => $product->barcode,
                'category'       => $product->category?->name ?? 'N/A',
                'supplier'       => $product->supplier?->name ?? 'N/A',
                'price'          => $product->price,
                'cost'           => $product->cost,
                'reorder_level'  => $product->reorder_level,
                'description'    => $product->description,
                'stock_quantity' => $product->stock_quantity,
                'status'         => $status,
                'image'          => $product->image
                    ? (str_starts_with($product->image, 'http') ? $product->image : url('storage/' . $product->image))
                    : null,
            ];

            return response()->json([
                'status'  => 200,
                'message' => 'Product retrieved successfully',
                'data'    => $productData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching product: ' . $e->getMessage(),
            ]);
        }
    }

    // Store new product
    public function store(Request $request)
    {

        try {
            $validated = $request->validate([
                'name'           => 'required|string|max:255',
                'category_id'    => 'required|exists:categories,id',
                'supplier_id'    => 'required|exists:suppliers,id',
                'sku'            => 'required|unique:products,sku',
                'barcode'        => 'nullable|string',
                'description'    => 'nullable|string',
                'cost'           => 'required|numeric',
                'price'          => 'nullable|numeric',
                'stock_quantity' => 'required|integer',
                'reorder_level'  => 'nullable|integer',
                'image'          => 'required|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            //  Upload image to Cloudinary
            if ($request->hasFile('image')) {
                $uploadedImage = Cloudinary::upload(
                    $request->file('image')->getRealPath(), ['folder' => 'products']
                );

                // Save image URL
                $validated['image'] = $uploadedImage->getSecurePath();
            }

            //  Auto-calculate price
            if (! isset($validated['price'])) {
                $validated['price'] = round($validated['cost'] * 1.2, 2);
            }

            $product = Product::create($validated);

            return response()->json([
                'status'  => 201,
                'message' => 'Product created successfully',
                'data'    => $product,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Update existing product
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name'           => 'sometimes|required|string|max:255',
                'category_id'    => 'sometimes|required|exists:categories,id',
                'supplier_id'    => 'sometimes|required|exists:suppliers,id',
                'sku'            => 'sometimes|required|unique:products,sku,' . $id,
                'barcode'        => 'nullable|string',
                'description'    => 'nullable|string',
                'cost'           => 'sometimes|required|numeric',
                'price'          => 'nullable|numeric',
                'stock_quantity' => 'sometimes|required|integer',
                'reorder_level'  => 'nullable|integer',
                'image'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $product = Product::findOrFail($id);

            //  If new image uploaded
            if ($request->hasFile('image')) {

                //  Delete old image from Cloudinary
                if (! empty($product->image)) {
                    // Extract public_id from URL
                    $publicId = pathinfo(parse_url($product->image, PHP_URL_PATH), PATHINFO_FILENAME);
                    $folder   = dirname(parse_url($product->image, PHP_URL_PATH));
                    $publicId = trim($folder, '/') . '/' . $publicId;

                    Cloudinary::destroy($publicId);
                }

                // Upload new image
                $uploadedImage = Cloudinary::upload(
                    $request->file('image')->getRealPath(),
                    ['folder' => 'products']
                );

                $validated['image'] = $uploadedImage->getSecurePath();
            }

            // Auto-calculate price
            if (isset($validated['cost']) && ! isset($validated['price'])) {
                $validated['price'] = round($validated['cost'] * 1.2, 2);
            }

            $product->update($validated);

            return response()->json([
                'status'  => 200,
                'message' => 'Product updated successfully',
                'data'    => $product,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Delete product
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // ✅ Delete image from Cloudinary first
            if (! empty($product->image)) {
                                                                      // Extract public_id from Cloudinary URL
                $path     = parse_url($product->image, PHP_URL_PATH); // /image/upload/v123/products/abc.jpg
                $filename = pathinfo($path, PATHINFO_FILENAME);       // abc
                $folder   = dirname($path);                           // /image/upload/v123/products
                $publicId = trim($folder, '/') . '/' . $filename;     // products/abc

                Cloudinary::destroy($publicId);
            }

                                     // ✅ Delete product
            $product->forceDelete(); // or delete() for soft delete

            return response()->json([
                'status'  => 200,
                'message' => 'Product and image deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Delete product error: ' . $e->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
