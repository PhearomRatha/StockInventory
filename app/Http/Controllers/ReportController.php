<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Sales as Sale;
use App\Models\SaleItem;
use App\Models\Payments as Payment;
use App\Models\Products as Product;
use App\Models\Stock_ins as StockIn;
use App\Models\Stock_outs as StockOut;
use App\Models\Customers as Customer;
use App\Models\Activity_logs as ActivityLog;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // -----------------------------
    // 1. Sales Report
    // -----------------------------
    public function salesReport(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:daily,monthly,yearly',
            'date' => 'nullable|date',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2030'
        ]);

        $cacheKey = 'report_sales_' . md5(serialize($request->all()));
        return Cache::remember($cacheKey, 12, function () use ($request) {
            $query = Sale::with('saleItems.product', 'customer', 'soldBy');

            // Filter by period
            if ($request->period === 'daily' && $request->date) {
                $query->whereDate('created_at', $request->date);
            } elseif ($request->period === 'monthly' && $request->month && $request->year) {
                $query->whereYear('created_at', $request->year)->whereMonth('created_at', $request->month);
            } elseif ($request->period === 'yearly' && $request->year) {
                $query->whereYear('created_at', $request->year);
            }

            $sales = $query->get();

            // Calculate totals
            $totalSales = $sales->sum('total_amount');
            $totalInvoices = $sales->count();
            $totalDiscounts = $sales->sum(function ($sale) {
                return $sale->saleItems->sum('discount');
            });
            $totalItemsSold = $sales->sum(function ($sale) {
                return $sale->saleItems->sum('quantity');
            });

            // Best-selling products
            $productSales = [];
            foreach ($sales as $sale) {
                foreach ($sale->saleItems as $item) {
                    $productName = $item->product->name ?? 'Unknown';
                    if (!isset($productSales[$productName])) {
                        $productSales[$productName] = 0;
                    }
                    $productSales[$productName] += $item->quantity;
                }
            }
            arsort($productSales);
            $bestSellingProduct = !empty($productSales) ? key($productSales) : null;

            // Sales by payment method
            $salesByPaymentMethod = Payment::where('reference_type', 'sale')
                ->whereIn('reference_id', $sales->pluck('id'))
                ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->groupBy('payment_method')
                ->get();

            // Sales by customer
            $salesByCustomer = $sales->groupBy('customer.name')->map(function ($group) {
                return [
                    'customer' => $group->first()->customer->name ?? 'Unknown',
                    'total_sales' => $group->sum('total_amount'),
                    'invoice_count' => $group->count()
                ];
            })->sortByDesc('total_sales')->take(10);

            return response()->json([
                'status' => true,
                'period' => $request->period ?? 'all',
                'total_sales' => $totalSales,
                'total_invoices' => $totalInvoices,
                'total_discounts' => $totalDiscounts,
                'total_items_sold' => $totalItemsSold,
                'best_selling_product' => $bestSellingProduct,
                'sales_by_payment_method' => $salesByPaymentMethod,
                'top_customers' => $salesByCustomer->values()
            ]);
        });
    }

    // -----------------------------
    // 2. Financial Report (Income & Expense)
    // -----------------------------
    public function financialReport(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:today,monthly,yearly',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2030'
        ]);

        $cacheKey = 'report_financial_' . md5(serialize($request->all()));
        return Cache::remember($cacheKey, 12, function () use ($request) {
            $query = Payment::query();

            // Filter by period
            if ($request->period === 'today') {
                $query->whereDate('payment_date', today());
            } elseif ($request->period === 'monthly' && $request->month && $request->year) {
                $query->whereYear('payment_date', $request->year)->whereMonth('payment_date', $request->month);
            } elseif ($request->period === 'yearly' && $request->year) {
                $query->whereYear('payment_date', $request->year);
            }

            $payments = $query->get();

            $totalIncome = $payments->where('payment_type', 'income')->sum('amount');
            $totalExpense = $payments->where('payment_type', 'expense')->sum('amount');
            $netProfit = $totalIncome - $totalExpense;

            // By payment method
            $incomeByMethod = $payments->where('payment_type', 'income')->groupBy('payment_method')->map->sum('amount');
            $expenseByMethod = $payments->where('payment_type', 'expense')->groupBy('payment_method')->map->sum('amount');

            // By type
            $incomeByType = $payments->where('payment_type', 'income')->groupBy('reference_type')->map->sum('amount');
            $expenseByType = $payments->where('payment_type', 'expense')->groupBy('reference_type')->map->sum('amount');

            return response()->json([
                'status' => true,
                'period' => $request->period ?? 'all',
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_profit' => $netProfit,
                'income_by_method' => $incomeByMethod,
                'expense_by_method' => $expenseByMethod,
                'income_by_type' => $incomeByType,
                'expense_by_type' => $expenseByType
            ]);
        });
    }

   
    public function stockReport(Request $request)
    {
        return Cache::remember('report_stock', 12, function () {
            $products = Product::with('category')->get();

            $stockData = $products->map(function ($product) {
                $stockIns = StockIn::where('product_id', $product->id)->sum('quantity');
                $stockOuts = StockOut::where('product_id', $product->id)->sum('quantity') +
                             SaleItem::where('product_id', $product->id)->sum('quantity');
                $currentStock = $product->stock_quantity;
                $stockValue = $currentStock * $product->price;
                $percent = $stockIns > 0 ? ($currentStock / $stockIns) * 100 : 0;

                if ($currentStock == 0) {
                    $message = 'Out-of-Stock';
                } elseif ($percent >= 50) {
                    $message = 'In Stock';
                } elseif ($percent >= 10) {
                    $message = 'Low Stock';
                } else {
                    $message = 'Very Low Stock';
                }

                $lowStock = $percent < 50; // Low stock if less than 50%

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category' => $product->category->name ?? 'Unknown',
                    'current_stock' => $currentStock,
                    'stock_ins' => $stockIns,
                    'stock_outs' => $stockOuts,
                    'stock_value' => $stockValue,
                    'low_stock' => $lowStock,
                    'message' => $message
                ];
            });

            $totalStockValue = $stockData->sum('stock_value');
            $lowStockProducts = $stockData->where('low_stock', true);

            $totalInStock = $stockData->where('messsage', 'In Stock')->count();
            $totalLowStock = $stockData->whereIn('message', ['Low Stock', 'Very Low Stock'])->count();
            $totalOutOfStock = $stockData->where('message', 'Out-of-Stock')->count();

            return response()->json([
                'status' => true,
                'total_stock_value' => $totalStockValue,
                'total_in_stock' => $totalInStock,
                'total_low_stock' => $totalLowStock,
                'total_out_of_stock' => $totalOutOfStock,
                'low_stock_products' => $lowStockProducts,
                'stock_details' => $stockData
            ]);
        });
    }

 
    
   
}