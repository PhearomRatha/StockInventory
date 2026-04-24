<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\CacheHelper;
use App\Helpers\ImageHelper;
use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Products as Product;
use App\Models\Stock_ins;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get total number of products
     */
    public function totalPro()
    {
        return CacheHelper::remember(CacheHelper::productsTotalKey(), 60, function () {
            return ResponseHelper::success('Total products retrieved successfully', [
                'total_products' => Product::count(),
            ]);
        });
    }

    /**
     * Get stock status
     */
    public function stock()
    {
        return CacheHelper::remember(CacheHelper::productsStockKey(), 10, function () {
            $lowStock = Product::select('id', 'name', 'stock_quantity')
                ->whereBetween('stock_quantity', [1, 9])
                ->limit(50)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'stock_quantity' => $p->stock_quantity,
                    'status' => 'Low Stock',
                ]);

            $outOfStock = Product::select('id', 'name', 'stock_quantity')
                ->where('stock_quantity', 0)
                ->limit(50)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'stock_quantity' => $p->stock_quantity,
                    'status' => 'Out of Stock',
                ]);

            return ResponseHelper::success('Stock status retrieved successfully', [
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
            ]);
        });
    }

    /**
     * Get paginated products
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 7);
        $page = $request->query('page', 1);
        $cacheKey = CacheHelper::productsKey()."_page_{$page}_per_{$perPage}";

        return CacheHelper::remember($cacheKey, 30, function () use ($perPage) {
            $products = Product::with(['category:id,name', 'supplier:id,name'])
                ->paginate($perPage);

            $products->getCollection()->transform(fn ($product) => [
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
            ]);

            return ResponseHelper::success('All products retrieved successfully', $products);
        });
    }

    /**
     * Get single product by ID
     */
    public function show($id)
    {
        $cacheKey = "product_show_{$id}";

        return CacheHelper::remember($cacheKey, 30, function () use ($id) {
            $product = Product::with(['category:id,name', 'supplier:id,name,email,phone'])->findOrFail($id);

            $status = $product->stock_quantity == 0 ? 'Out of Stock' :
                      ($product->stock_quantity <= $product->reorder_level ? 'Low Stock' : 'In Stock');

            return ResponseHelper::success('Product retrieved successfully', [
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
                'image' => $product->image ? ImageHelper::getImageUrl($product->image) : null,
            ]);
        });
    }

    /**
     * Create new product with initial stock
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        // Generate SKU
        $validated['sku'] ??= strtoupper(substr($validated['name'], 0, 3)).'-'.time();

        // Generate Barcode
        if (! isset($validated['barcode'])) {
            do {
                $barcode = mt_rand(100000000000, 999999999999);
            } while (Product::where('barcode', $barcode)->exists());
            $validated['barcode'] = (string) $barcode;
        }

        // Upload image
        $validated['image'] = $request->hasFile('image')
            ? ImageHelper::uploadToCloudinary($request->file('image'), 'products')
            : 'https://i.pinimg.com/736x/22/ae/3b/22ae3bb2f7b46bed0e3a99a025835ab0.jpg';

        // Auto price & reorder_level
        $validated['price'] ??= round($validated['cost'] * 1.2, 2);
        $validated['reorder_level'] ??= ($validated['stock_quantity'] <= 20 ? 5 : ($validated['stock_quantity'] <= 50 ? 10 : round($validated['stock_quantity'] * 0.2)));

        $product = Product::create($validated);

        // Initial stock entry
        if ($validated['stock_quantity'] > 0) {
            Stock_ins::create([
                'product_id' => $product->id,
                'quantity' => $validated['stock_quantity'],
                'description' => 'Initial stock on product creation',
                'added_by' => auth()->id() ?? null,
            ]);
        }

        ActivityLogHelper::logCreated('products', $product->id);

        // Clear caches
        CacheHelper::forget(CacheHelper::productsKey());
        CacheHelper::forget(CacheHelper::productsTotalKey());
        CacheHelper::forget(CacheHelper::productsStockKey());

        return ResponseHelper::success('Product created successfully with initial stock', $product, 201);
    }

    /**
     * Update product and adjust stock if quantity changed
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $validated = $request->validated();

        $product = Product::findOrFail($id);

        // Handle image upload
        if ($request->hasFile('image')) {
            if (! empty($product->image)) {
                ImageHelper::deleteFromCloudinary($product->image, 'products');
            }
            $validated['image'] = ImageHelper::uploadToCloudinary($request->file('image'), 'products');
        }

        // Auto price & reorder_level
        if (isset($validated['cost']) && ! isset($validated['price'])) {
            $validated['price'] = round($validated['cost'] * 1.2, 2);
        }
        if (isset($validated['stock_quantity']) && ! isset($validated['reorder_level'])) {
            $stock = $validated['stock_quantity'];
            $validated['reorder_level'] = $stock <= 20 ? 5 : ($stock <= 50 ? 10 : round($stock * 0.2));

            // Add stock_ins entry for increase
            $diff = $stock - $product->stock_quantity;
            if ($diff > 0) {
                Stock_ins::create([
                    'product_id' => $product->id,
                    'quantity' => $diff,
                    'description' => 'Stock adjusted on product update',
                    'added_by' => auth()->id() ?? null,
                ]);
            }
        }

        $product->update($validated);
        ActivityLogHelper::logUpdated('products', $product->id);

        // Clear caches
        CacheHelper::forget(CacheHelper::productsKey());
        CacheHelper::forget(CacheHelper::productsTotalKey());
        CacheHelper::forget(CacheHelper::productsStockKey());
        CacheHelper::forget("product_show_{$id}");

        return ResponseHelper::success('Product updated successfully', $product);
    }

    /**
     * Delete product and its image
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if (! empty($product->image)) {
            ImageHelper::deleteFromCloudinary($product->image, 'products');
        }

        $product->forceDelete();
        ActivityLogHelper::logDeleted('products', $product->id);

        // Clear caches
        CacheHelper::forget(CacheHelper::productsKey());
        CacheHelper::forget(CacheHelper::productsTotalKey());
        CacheHelper::forget(CacheHelper::productsStockKey());
        CacheHelper::forget("product_show_{$id}");

        return ResponseHelper::success('Product and image deleted successfully');
    }
}
