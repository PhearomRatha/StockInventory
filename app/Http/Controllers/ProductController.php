<?php

namespace App\Http\Controllers;

use App\Models\Products as Product;
use App\Helpers\ResponseHelper;
use App\Helpers\ActivityLogHelper;
use App\Helpers\ImageHelper;
use App\Helpers\CacheHelper;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // --------------------------
    // Count total products
    // --------------------------
    public function totalPro()
    {
        try {
            return CacheHelper::remember(CacheHelper::productsTotalKey(), 10, function () {
                $total = Product::count();
                return ResponseHelper::success('Total products retrieved successfully', ['total_products' => $total]);
            });
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    // --------------------------
    // Check stock status
    // --------------------------
    public function stock()
    {
        try {
            return CacheHelper::remember(CacheHelper::productsStockKey(), 5, function () {
                $products = Product::all();
                $lowStock = [];
                $outOfStock = [];

                foreach ($products as $product) {
                    if ($product->stock_quantity == 0) {
                        $outOfStock[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'stock_quantity' => $product->stock_quantity,
                            'status' => 'Out of Stock'
                        ];
                    } elseif ($product->is_low_stock) {
                        $lowStock[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'stock_quantity' => $product->stock_quantity,
                            'status' => 'Low Stock'
                        ];
                    }
                }

                return ResponseHelper::success('Stock status retrieved successfully', [
                    'low_stock' => $lowStock,
                    'out_of_stock' => $outOfStock
                ]);
            });

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    // --------------------------
    // List all products
    // --------------------------
    public function index(Request $request)
    {
        try {
            return CacheHelper::remember(CacheHelper::productsKey(), 12, function () use ($request) {
                
        $perPage = $request->query('per_page', 7);

        // Fetch products with pagination
        $products = Product::with(['category', 'supplier'])->paginate($perPage);

                $productsTable = $products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                        'category' => $product->category?->name ?? 'N/A',
                        'supplier' => $product->supplier?->name ?? 'N/A',
                        'price' => $product->price,
                        'cost' => $product->cost,
                        'reorder_level' => $product->reorder_level,
                        'description' => $product->description,
                        'stock_quantity' => $product->stock_quantity,
                        'status' => $product->stock_status,
                        'low_stock' => $product->is_low_stock,
                        'image' => ImageHelper::getImageUrl($product->image),
                    ];
                });

                return ResponseHelper::success('All products retrieved successfully', $productsTable);
            });

        } catch (\Exception $e) {
            return ResponseHelper::error('Error fetching products: ' . $e->getMessage());
        }
    }

    // --------------------------
    // Show single product
    // --------------------------
    public function show($id)
    {
        try {
            $product = Product::with(['category', 'supplier'])->findOrFail($id);

            $status = $product->stock_quantity == 0 ? 'Out of Stock'
                : ($product->reorder_level > 0 && $product->stock_quantity <= $product->reorder_level ? 'Low Stock'
                    : 'In Stock');

            return response()->json([
                'status' => 200,
                'message' => 'Product retrieved successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'category' => $product->category?->name ?? 'N/A',
                    'supplier' => $product->supplier?->name ?? 'N/A',
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'reorder_level' => $product->reorder_level,
                    'description' => $product->description,
                    'stock_quantity' => $product->stock_quantity,
                    'status' => $status,
                    'image' => $product->image
                        ? (str_starts_with($product->image, 'http') ? $product->image : url('storage/' . $product->image))
                        : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error fetching product: ' . $e->getMessage()]);
        }
    }

    // --------------------------
    // Store new product
    // --------------------------
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'           => 'required|string|max:255',
                'category_id'    => 'required|exists:categories,id',
                'supplier_id'    => 'required|exists:suppliers,id',
                'sku'            => 'nullable|unique:products,sku',
                'barcode'        => 'nullable|string',
                'description'    => 'nullable|string',
                'cost'           => 'required|numeric',
                'price'          => 'nullable|numeric',
                'stock_quantity' => 'required|integer',
                'reorder_level'  => 'nullable|integer',
                'image'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Auto-generate SKU
            if (!isset($validated['sku'])) {
                $prefix = strtoupper(substr($validated['name'], 0, 3));
                $validated['sku'] = $prefix . '-' . time();
            }

            // Auto-generate Barcode
            if (!isset($validated['barcode'])) {
                do {
                    $barcode = mt_rand(100000000000, 999999999999);
                } while (Product::where('barcode', $barcode)->exists());
                $validated['barcode'] = (string)$barcode;
            }

            // Upload image or placeholder
            if ($request->hasFile('image')) {
                $validated['image'] = ImageHelper::uploadToCloudinary($request->file('image'), 'products');
            } else {
                $validated['image'] = "https://i.pinimg.com/736x/22/ae/3b/22ae3bb2f7b46bed0e3a99a025835ab0.jpg";
            }

            // Auto-calculate price
            if (!isset($validated['price'])) {
                $validated['price'] = round($validated['cost'] * 1.2, 2);
            }

            // Auto-calculate reorder_level based on stock_quantity
            if (!isset($validated['reorder_level'])) {
                $stock = $validated['stock_quantity'];
                if ($stock <= 20) {
                    $validated['reorder_level'] = 5;
                } elseif ($stock <= 50) {
                    $validated['reorder_level'] = 10;
                } else {
                    $validated['reorder_level'] = round($stock * 0.2);
                }
            }

            $product = Product::create($validated);

            // Log activity
            ActivityLogHelper::logCreated('products', $product->id);

            return ResponseHelper::success('Product created successfully', $product, 201);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // --------------------------
    // Update existing product
    // --------------------------
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

            // Handle image upload
            if ($request->hasFile('image')) {
                if (!empty($product->image)) {
                    ImageHelper::deleteFromCloudinary($product->image, 'products');
                }

                $validated['image'] = ImageHelper::uploadToCloudinary($request->file('image'), 'products');
            }

            // Auto-calculate price if cost changed
            if (isset($validated['cost']) && !isset($validated['price'])) {
                $validated['price'] = round($validated['cost'] * 1.2, 2);
            }

            // Auto-calculate reorder_level if stock_quantity changed
            if (isset($validated['stock_quantity']) && !isset($validated['reorder_level'])) {
                $stock = $validated['stock_quantity'];
                if ($stock <= 20) {
                    $validated['reorder_level'] = 5;
                } elseif ($stock <= 50) {
                    $validated['reorder_level'] = 10;
                } else {
                    $validated['reorder_level'] = round($stock * 0.2);
                }
            }

            $product->update($validated);

            ActivityLogHelper::logUpdated('products', $product->id);

            return ResponseHelper::success('Product updated successfully', $product);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // --------------------------
    // Delete product
    // --------------------------
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Delete image from Cloudinary
            if (!empty($product->image)) {
                ImageHelper::deleteFromCloudinary($product->image, 'products');
            }

            $product->forceDelete();

            ActivityLogHelper::logDeleted('products', $product->id);

            return ResponseHelper::success('Product and image deleted successfully');

        } catch (\Exception $e) {
            return ResponseHelper::error('Delete product error: ' . $e->getMessage());
        }
    }
}
