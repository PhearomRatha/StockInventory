<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Products;
use App\Models\Sales;
use App\Models\Stock_ins;
use App\Models\Stock_outs;
use App\Models\Customers;
use App\Models\Suppliers;
use App\Models\User;
use App\Helpers\ResponseHelper;

class DashboardController extends Controller
{
    /**
     * Get dashboard data
     */
    public function index()
    {
        try {
            $cacheKey = 'dashboard_' . now()->format('Y-m-d-H'); // Cache for 1 hour
            $cacheTTL = 3600; // 1 hour
            
            // Try to get cached data
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                return ResponseHelper::success('Dashboard data retrieved successfully', $cachedData);
            }

            // Basic counts (fast queries)
            $totalProducts = Products::count();
            $totalUsers = User::count();
            $totalSales = Sales::count();
            $totalRevenue = Sales::sum('total_amount');
            $totalCustomers = Customers::count();
            $totalSuppliers = Suppliers::count();

            $lowStock = Products::where('is_low_stock', true)->count();
            $outOfStock = Products::where('stock_quantity', 0)->count();

            $recentSales = Sales::select('id', 'customer_id', 'sold_by', 'total_amount', 'payment_status', 'created_at')
                ->with(['customer:id,name'])
                ->latest()
                ->take(5)
                ->get();
            $topProducts = Products::select('id', 'name', 'stock_quantity', 'price')
                ->orderBy('stock_quantity', 'desc')
                ->take(5)
                ->get();

            // Current Month Revenue
            $currentMonthRevenue = Sales::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount');

            // Previous Month Revenue
            $previousMonth = now()->subMonth();
            $previousMonthRevenue = Sales::whereMonth('created_at', $previousMonth->month)
                ->whereYear('created_at', $previousMonth->year)
                ->sum('total_amount');

            // Calculate percentage difference
            if ($previousMonthRevenue > 0) {
                $percentageChange = (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100;
            } else {
                $percentageChange = $currentMonthRevenue > 0 ? 100 : 0;
            }

            // ==================== PERCENTAGE DISTRIBUTIONS ====================
            
            // Customer Sales Percentage Distribution - Use join instead of with()
            $customerSalesData = Sales::selectRaw('customer_id, SUM(total_amount) as total')
                ->groupBy('customer_id')
                ->get();
            
            $customerPercentages = [];
            $totalCustomerSales = $customerSalesData->sum('total');
            $customerIds = $customerSalesData->pluck('customer_id')->filter()->toArray();
            $customersMap = Customers::whereIn('id', $customerIds)->pluck('name', 'id')->toArray();
            
            foreach ($customerSalesData as $sale) {
                if ($sale->customer_id && isset($customersMap[$sale->customer_id]) && $totalCustomerSales > 0) {
                    $customerPercentages[] = [
                        'customer_id' => $sale->customer_id,
                        'customer_name' => $customersMap[$sale->customer_id],
                        'total_sales' => $sale->total,
                        'percentage' => round(($sale->total / $totalCustomerSales) * 100, 2)
                    ];
                }
            }
            
            // Sort by percentage descending
            usort($customerPercentages, function($a, $b) {
                return $b['percentage'] <=> $a['percentage'];
            });

            // Product Sales Percentage Distribution (using stock_outs)
            $productSalesData = Stock_outs::selectRaw('product_id, SUM(total_amount) as total_sales, SUM(quantity) as total_quantity')
                ->groupBy('product_id')
                ->get();
            
            $productPercentages = [];
            $totalProductSales = $productSalesData->sum('total_sales');
            $productIds = $productSalesData->pluck('product_id')->filter()->toArray();
            $productsMap = Products::whereIn('id', $productIds)->pluck('name', 'id')->toArray();
            
            foreach ($productSalesData as $item) {
                if ($item->product_id && isset($productsMap[$item->product_id]) && $totalProductSales > 0) {
                    $productPercentages[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $productsMap[$item->product_id],
                        'total_sales' => $item->total_sales,
                        'total_quantity' => $item->total_quantity,
                        'percentage' => round(($item->total_sales / $totalProductSales) * 100, 2)
                    ];
                }
            }
            
            // Sort by percentage descending
            usort($productPercentages, function($a, $b) {
                return $b['percentage'] <=> $a['percentage'];
            });

            // Stock In vs Stock Out Percentage
            $totalStockIns = Stock_ins::sum('quantity');
            $totalStockOuts = Stock_outs::sum('quantity');
            $totalStockMovement = $totalStockIns + $totalStockOuts;
            
            $stockInPercentage = $totalStockMovement > 0 ? round(($totalStockIns / $totalStockMovement) * 100, 2) : 0;
            $stockOutPercentage = $totalStockMovement > 0 ? round(($totalStockOuts / $totalStockMovement) * 100, 2) : 0;

            // Supplier Percentage Distribution (based on stock ins)
            $supplierStockIns = Stock_ins::selectRaw('supplier_id, SUM(quantity) as total_quantity, SUM(total_cost) as total_cost')
                ->groupBy('supplier_id')
                ->get();
            
            $supplierPercentages = [];
            $totalSupplierQuantity = $supplierStockIns->sum('total_quantity');
            $supplierIds = $supplierStockIns->pluck('supplier_id')->filter()->toArray();
            $suppliersMap = Suppliers::whereIn('id', $supplierIds)->pluck('name', 'id')->toArray();
            
            foreach ($supplierStockIns as $item) {
                if ($item->supplier_id && isset($suppliersMap[$item->supplier_id]) && $totalSupplierQuantity > 0) {
                    $supplierPercentages[] = [
                        'supplier_id' => $item->supplier_id,
                        'supplier_name' => $suppliersMap[$item->supplier_id],
                        'total_quantity' => $item->total_quantity,
                        'total_cost' => $item->total_cost,
                        'percentage' => round(($item->total_quantity / $totalSupplierQuantity) * 100, 2)
                    ];
                }
            }
            
            // Sort by percentage descending
            usort($supplierPercentages, function($a, $b) {
                return $b['percentage'] <=> $a['percentage'];
            });

            // ==================== MONTH-OVER-MONTH COMPARISONS ====================
            
            // Current and Previous Month data
            $currentMonth = now();
            $lastMonth = now()->subMonth();
            
            // Stock In - Current vs Last Month
            $currentMonthStockIn = Stock_ins::whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->sum('quantity');
            $lastMonthStockIn = Stock_ins::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->sum('quantity');
            
            $stockInChange = $lastMonthStockIn > 0 
                ? round((($currentMonthStockIn - $lastMonthStockIn) / $lastMonthStockIn) * 100, 2)
                : ($currentMonthStockIn > 0 ? 100 : 0);

            // Stock Out - Current vs Last Month
            $currentMonthStockOut = Stock_outs::whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->sum('quantity');
            $lastMonthStockOut = Stock_outs::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->sum('quantity');
            
            $stockOutChange = $lastMonthStockOut > 0 
                ? round((($currentMonthStockOut - $lastMonthStockOut) / $lastMonthStockOut) * 100, 2)
                : ($currentMonthStockOut > 0 ? 100 : 0);

            // Total Sales Count - Current vs Last Month
            $currentMonthSalesCount = Sales::whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->count();
            $lastMonthSalesCount = Sales::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->count();
            
            $salesCountChange = $lastMonthSalesCount > 0 
                ? round((($currentMonthSalesCount - $lastMonthSalesCount) / $lastMonthSalesCount) * 100, 2)
                : ($currentMonthSalesCount > 0 ? 100 : 0);

            // Customer Count - Current vs Last Month (new customers)
            $currentMonthCustomers = Customers::whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->count();
            $lastMonthCustomers = Customers::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->count();
            
            $customerChange = $lastMonthCustomers > 0 
                ? round((($currentMonthCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100, 2)
                : ($currentMonthCustomers > 0 ? 100 : 0);

            // Supplier Count - Current vs Last Month
            $currentMonthSuppliers = Suppliers::whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->count();
            $lastMonthSuppliers = Suppliers::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->count();
            
            $supplierChange = $lastMonthSuppliers > 0 
                ? round((($currentMonthSuppliers - $lastMonthSuppliers) / $lastMonthSuppliers) * 100, 2)
                : ($currentMonthSuppliers > 0 ? 100 : 0);

            // Product Count - Current vs Last Month
            $currentMonthProducts = Products::whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->count();
            $lastMonthProducts = Products::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->count();
            
            $productChange = $lastMonthProducts > 0 
                ? round((($currentMonthProducts - $lastMonthProducts) / $lastMonthProducts) * 100, 2)
                : ($currentMonthProducts > 0 ? 100 : 0);

            $data = [
                'overview' => [
                    'total_products' => $totalProducts,
                    'total_customers' => $totalCustomers,
                    'total_suppliers' => $totalSuppliers,
                    'total_users' => $totalUsers,
                    'total_sales' => $totalSales,
                    'total_revenue' => $totalRevenue,
                    'total_stock_ins' => $totalStockIns,
                    'total_stock_outs' => $totalStockOuts,
                    // Monthly revenue
                    'monthly_revenue' => $currentMonthRevenue,
                    'previous_month_revenue' => $previousMonthRevenue,
                    'revenue_percentage_change' => round($percentageChange, 2),
                    // Customers - current month vs last month
                    'current_month_customers' => $currentMonthCustomers,
                    'last_month_customers' => $lastMonthCustomers,
                    'customers_percentage_change' => $customerChange,
                    // Products - current month vs last month
                    'current_month_products' => $currentMonthProducts,
                    'last_month_products' => $lastMonthProducts,
                    'products_percentage_change' => $productChange,
                    // Suppliers - current month vs last month
                    'current_month_suppliers' => $currentMonthSuppliers,
                    'last_month_suppliers' => $lastMonthSuppliers,
                    'suppliers_percentage_change' => $supplierChange,
                    // Sales - current month vs last month
                    'current_month_sales' => $currentMonthSalesCount,
                    'last_month_sales' => $lastMonthSalesCount,
                    'sales_percentage_change' => $salesCountChange,
                    // Stock In - current month vs last month
                    'current_month_stock_ins' => $currentMonthStockIn,
                    'last_month_stock_ins' => $lastMonthStockIn,
                    'stock_ins_percentage_change' => $stockInChange,
                    // Stock Out - current month vs last month
                    'current_month_stock_outs' => $currentMonthStockOut,
                    'last_month_stock_outs' => $lastMonthStockOut,
                    'stock_outs_percentage_change' => $stockOutChange,
                    // Stock status
                    'low_stock_count' => $lowStock,
                    'out_of_stock_count' => $outOfStock
                ],
                // Percentage Distributions
                'percentages' => [
                    'customers' => $customerPercentages,
                    'products' => $productPercentages,
                    'stock_in_out' => [
                        'stock_in_percentage' => $stockInPercentage,
                        'stock_out_percentage' => $stockOutPercentage,
                        'total_stock_ins' => $totalStockIns,
                        'total_stock_outs' => $totalStockOuts
                    ],
                    'suppliers' => $supplierPercentages
                ],
                // Month-over-Month Changes
                'month_comparison' => [
                    'revenue' => [
                        'current_month' => $currentMonthRevenue,
                        'last_month' => $previousMonthRevenue,
                        'change_percentage' => round($percentageChange, 2)
                    ],
                    'stock_in' => [
                        'current_month' => $currentMonthStockIn,
                        'last_month' => $lastMonthStockIn,
                        'change_percentage' => $stockInChange
                    ],
                    'stock_out' => [
                        'current_month' => $currentMonthStockOut,
                        'last_month' => $lastMonthStockOut,
                        'change_percentage' => $stockOutChange
                    ],
                    'sales_count' => [
                        'current_month' => $currentMonthSalesCount,
                        'last_month' => $lastMonthSalesCount,
                        'change_percentage' => $salesCountChange
                    ],
                    'new_customers' => [
                        'current_month' => $currentMonthCustomers,
                        'last_month' => $lastMonthCustomers,
                        'change_percentage' => $customerChange
                    ],
                    'new_suppliers' => [
                        'current_month' => $currentMonthSuppliers,
                        'last_month' => $lastMonthSuppliers,
                        'change_percentage' => $supplierChange
                    ],
                    'new_products' => [
                        'current_month' => $currentMonthProducts,
                        'last_month' => $lastMonthProducts,
                        'change_percentage' => $productChange
                    ]
                ],
                'recent_sales' => $recentSales,
                'top_products' => $topProducts
            ];
            
            // Cache the result for 1 hour
            Cache::put($cacheKey, $data, $cacheTTL);
            
            return ResponseHelper::success('Dashboard data retrieved successfully', $data);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
