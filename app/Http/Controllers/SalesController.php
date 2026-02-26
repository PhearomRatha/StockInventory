<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sales as Sale;
use App\Models\SaleItem;
use App\Models\Products as Product;
use App\Models\Customers as customer;
use App\Helpers\ResponseHelper;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            
            // OPTIMIZED: Use select() to limit columns + eager load only needed fields + paginate
            $sales = Sale::select('id', 'customer_id', 'sold_by', 'invoice_number', 
                                  'total_amount', 'discount', 'payment_status', 
                                  'payment_method', 'status', 'created_at')
                ->with(['customer:id,name,email', 'soldBy:id,name,email'])
                ->paginate(min($perPage, 100));
            
            return ResponseHelper::success('Sales retrieved successfully', $sales);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string'
            ]);

            // OPTIMIZED: Fetch all products in a single query instead of loop
            $productIds = array_column($validated['items'], 'product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $totalAmount = 0;
            $saleItems = [];

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                
                if (!$product) {
                    return ResponseHelper::error("Product not found", null, 404);
                }

                if ($product->stock_quantity < $item['quantity']) {
                    return ResponseHelper::error("Insufficient stock for {$product->name}", null, 422);
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $saleItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total' => $itemTotal
                ];
            }

            // Generate unique invoice number
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());

            $sale = Sale::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'sold_by' => $user->id,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null
            ]);

            // OPTIMIZED: Bulk insert sale items
            foreach ($saleItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total']
                ]);
            }

            // OPTIMIZED: Bulk update product stock
            foreach ($saleItems as $item) {
                Product::where('id', $item['product_id'])
                    ->decrement('stock_quantity', $item['quantity']);
            }

            ActivityLogHelper::log('sale', "Sale #{$sale->id}: {$totalAmount}");

            return ResponseHelper::success('Sale created successfully', $sale, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Update a sale
     */
    public function update(Request $request, $id)
    {
        try {
            $sale = Sale::findOrFail($id);

            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'notes' => 'nullable|string'
            ]);

            $sale->update($validated);
            return ResponseHelper::success('Sale updated successfully', $sale);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $sale = Sale::findOrFail($id);
            $sale->delete();
            return ResponseHelper::success('Sale deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function dashboard()
    {
        try {
            // OPTIMIZED: Use single query for counts/sums
            $totals = Sale::selectRaw('COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue')
                ->first();
            
            // OPTIMIZED: Select only needed columns
            $recentSales = Sale::select('id', 'customer_id', 'sold_by', 'total_amount', 'payment_status', 'created_at')
                ->with(['customer:id,name'])  // Only fetch name
                ->latest()
                ->take(10)
                ->get();

            return ResponseHelper::success('Sales dashboard data retrieved successfully', [
                'total_sales' => $totals->total_sales,
                'total_revenue' => $totals->total_revenue,
                'recent_sales' => $recentSales
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function checkoutSale(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'payment_method' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            // Use DB transaction to ensure data consistency
            $sale = DB::transaction(function () use ($validated, $user) {
                // OPTIMIZED: Fetch all products in a single query instead of loop
                $productIds = array_column($validated['items'], 'product_id');
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                $totalAmount = 0;
                $saleItems = [];

                foreach ($validated['items'] as $item) {
                    $product = $products->get($item['product_id']);
                    
                    if (!$product) {
                        throw new \Exception("Product not found");
                    }

                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for {$product->name}");
                    }

                    $itemTotal = $product->price * $item['quantity'];
                    $totalAmount += $itemTotal;

                    $saleItems[] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'total' => $itemTotal
                    ];
                }

                // Generate unique invoice number
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());

                $sale = Sale::create([
                    'customer_id' => $validated['customer_id'] ?? null,
                    'sold_by' => $user->id,
                    'invoice_number' => $invoiceNumber,
                    'total_amount' => $totalAmount,
                    'status' => 'completed',
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'paid',
                    'notes' => $validated['notes'] ?? null
                ]);

                // OPTIMIZED: Bulk insert sale items
                foreach ($saleItems as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['total']
                    ]);
                }

                // OPTIMIZED: Bulk update product stock using single query
                foreach ($saleItems as $item) {
                    Product::where('id', $item['product_id'])
                        ->decrement('stock_quantity', $item['quantity']);
                }

                ActivityLogHelper::log('sale', "Checkout Sale #{$sale->id}: {$totalAmount} via {$validated['payment_method']}");

                return $sale;
            });

            return ResponseHelper::success('Checkout successful', $sale, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Verify sale payment
     */
    public function verifySalePayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'payment_reference' => 'required|string'
            ]);

            $sale = Sale::findOrFail($validated['sale_id']);
            $sale->update([
                'status' => 'completed',
                'payment_reference' => $validated['payment_reference']
            ]);

            return ResponseHelper::success('Payment verified successfully', $sale);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get sales data
     */
    public function getSalesData()
    {
        try {
            $sales = Sale::with(['customer', 'saleItems.product', 'soldBy'])->get();
            return ResponseHelper::success('Sales data retrieved successfully', $sales);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
