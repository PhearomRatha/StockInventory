<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sales as Sale;
use App\Models\SaleItem;
use App\Models\Products as Product;
use App\Models\Customers as customer;
use App\Helpers\ResponseHelper;
use App\Helpers\ActivityLogHelper;

class SalesController extends Controller
{
    public function index()
    {
        try {
            $sales = Sale::with(['customer', 'items.product'])->get();
            return ResponseHelper::success('Sales retrieved successfully', $sales);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string'
            ]);

            $totalAmount = 0;
            $saleItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    return ResponseHelper::error("Insufficient stock for {$product->name}", null, 422);
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $saleItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $itemTotal
                ];
            }

            $sale = Sale::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null
            ]);

            foreach ($saleItems as $item) {
                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal']
                ]);

                $product = Product::find($item['product_id']);
                $product->stock_quantity -= $item['quantity'];
                $product->save();
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
            $totalSales = Sale::count();
            $totalRevenue = Sale::sum('total_amount');
            $recentSales = Sale::with(['customer'])->latest()->take(10)->get();

            return ResponseHelper::success('Sales dashboard data retrieved successfully', [
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'recent_sales' => $recentSales
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function checkoutSale(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'payment_method' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $totalAmount = 0;
            $saleItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    return ResponseHelper::error("Insufficient stock for {$product->name}", null, 422);
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $saleItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $itemTotal
                ];
            }

            $sale = Sale::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null
            ]);

            foreach ($saleItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal']
                ]);

                $product = Product::find($item['product_id']);
                $product->stock_quantity -= $item['quantity'];
                $product->save();
            }

            ActivityLogHelper::log('sale', "Checkout Sale #{$sale->id}: {$totalAmount} via {$validated['payment_method']}");

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
            $sales = Sale::with(['customer', 'items.product'])->get();
            return ResponseHelper::success('Sales data retrieved successfully', $sales);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
