<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Products;
use App\Models\Sales;
use App\Models\StockTransaction;
use App\Models\Customers;
use App\Models\Suppliers;
use App\Models\User;
use App\Models\WarehouseProduct;
use App\Helpers\ResponseHelper;

class DashboardController extends Controller
{
    /**
     * Get dashboard data
     */
    public function index()
    {
        try {
            $cacheKey = 'dashboard_' . now()->format('Y-m-d-H');
            $cacheTTL = 3600;

            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                return ResponseHelper::success('Dashboard data retrieved successfully', $cachedData);
            }

            $currentMonth = now()->month;
            $currentYear  = now()->year;
            $lastMonth    = now()->subMonth()->month;
            $lastYear     = now()->subMonth()->year;

            // ==================== OVERVIEW COUNTS ====================
            // Single query for all count-based metrics
            $overviewCounts = DB::selectOne("
                SELECT
                    (SELECT COUNT(*) FROM products)   AS total_products,
                    (SELECT COUNT(*) FROM users)      AS total_users,
                    (SELECT COUNT(*) FROM sales)      AS total_sales,
                    (SELECT COALESCE(SUM(total), 0) FROM sales) AS total_revenue,
                    (SELECT COUNT(*) FROM customers)  AS total_customers,
                    (SELECT COUNT(*) FROM suppliers)  AS total_suppliers,
                    (SELECT COUNT(*) FROM warehouse_products WHERE quantity <= 5) AS low_stock_count,
                    (SELECT COUNT(*) FROM warehouse_products WHERE quantity = 0) AS out_of_stock_count,
                    (SELECT COALESCE(SUM(quantity), 0) FROM stock_transactions WHERE type = 'PURCHASE')  AS total_stock_ins,
                    (SELECT COALESCE(SUM(quantity), 0) FROM stock_transactions WHERE type = 'SALE') AS total_stock_outs
            ");

            // ==================== MONTH-OVER-MONTH (single query each) ====================

            // Revenue - current & last month in one query
            $revenueComparison = Sales::selectRaw("
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN total ELSE 0 END) AS current_month,
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN total ELSE 0 END) AS last_month
            ", [$currentMonth, $currentYear, $lastMonth, $lastYear])->first();

            // Sales count - current & last month in one query
            $salesComparison = Sales::selectRaw("
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) AS current_month,
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) AS last_month
            ", [$currentMonth, $currentYear, $lastMonth, $lastYear])->first();

            // Stock In (PURCHASE) - current & last month in one query
            $stockInComparison = StockTransaction::selectRaw("
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? AND type = 'PURCHASE' THEN quantity ELSE 0 END) AS current_month,
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? AND type = 'PURCHASE' THEN quantity ELSE 0 END) AS last_month
            ", [$currentMonth, $currentYear, $lastMonth, $lastYear])->first();

            // Stock Out (SALE) - current & last month in one query
            $stockOutComparison = StockTransaction::selectRaw("
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? AND type = 'SALE' THEN quantity ELSE 0 END) AS current_month,
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? AND type = 'SALE' THEN quantity ELSE 0 END) AS last_month
            ", [$currentMonth, $currentYear, $lastMonth, $lastYear])->first();

            // New Customers - current & last month in one query
            $customersComparison = Customers::selectRaw("
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) AS current_month,
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) AS last_month
            ", [$currentMonth, $currentYear, $lastMonth, $lastYear])->first();

            // New Suppliers - current & last month in one query
            $suppliersComparison = Suppliers::selectRaw("
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) AS current_month,
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) AS last_month
            ", [$currentMonth, $currentYear, $lastMonth, $lastYear])->first();

            // New Products - current & last month in one query
            $productsComparison = Products::selectRaw("
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) AS current_month,
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) AS last_month
            ", [$currentMonth, $currentYear, $lastMonth, $lastYear])->first();

            // ==================== PERCENTAGE DISTRIBUTIONS (JOIN instead of double query) ====================

            // Customer Sales Distribution - single JOIN query
            $customerSalesData = Sales::selectRaw('sales.customer_id, customers.name AS customer_name, SUM(sales.total) AS total')
                ->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->groupBy('sales.customer_id', 'customers.name')
                ->get();

            $totalCustomerSales = $customerSalesData->sum('total');
            $customerPercentages = $customerSalesData
                ->filter(fn($s) => $totalCustomerSales > 0)
                ->map(fn($s) => [
                    'customer_id'   => $s->customer_id,
                    'customer_name' => $s->customer_name,
                    'total_sales'   => $s->total,
                    'percentage'    => round(($s->total / $totalCustomerSales) * 100, 2),
                ])
                ->sortByDesc('percentage')
                ->values()
                ->toArray();

            // Product Sales Distribution - single JOIN query from sale_items (new schema)
            $productSalesData = DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->selectRaw('sale_items.product_id, products.name AS product_name, SUM(sale_items.total) AS total_sales, SUM(sale_items.quantity) AS total_quantity')
                ->groupBy('sale_items.product_id', 'products.name')
                ->get();

            $totalProductSales = $productSalesData->sum('total_sales');
            $productPercentages = $productSalesData
                ->filter(fn($p) => $totalProductSales > 0)
                ->map(fn($p) => [
                    'product_id'     => $p->product_id,
                    'product_name'   => $p->product_name,
                    'total_sales'    => $p->total_sales,
                    'total_quantity' => $p->total_quantity,
                    'percentage'     => round(($p->total_sales / $totalProductSales) * 100, 2),
                ])
                ->sortByDesc('percentage')
                ->values()
                ->toArray();

            // Supplier Distribution - legacy removed, purchases no longer track supplier directly in stock_tx (use products.supplier_id if needed)
            $supplierStockIns = collect([]);

            $totalSupplierQty = $supplierStockIns->sum('total_quantity');
            $supplierPercentages = $supplierStockIns
                ->filter(fn($s) => $totalSupplierQty > 0)
                ->map(fn($s) => [
                    'supplier_id'    => $s->supplier_id,
                    'supplier_name'  => $s->supplier_name,
                    'total_quantity' => $s->total_quantity,
                    'total_cost'     => $s->total_cost,
                    'percentage'     => round(($s->total_quantity / $totalSupplierQty) * 100, 2),
                ])
                ->sortByDesc('percentage')
                ->values()
                ->toArray();

            // Stock In vs Out percentage
            $totalStockMovement = $overviewCounts->total_stock_ins + $overviewCounts->total_stock_outs;
            $stockInPercentage  = $totalStockMovement > 0 ? round(($overviewCounts->total_stock_ins  / $totalStockMovement) * 100, 2) : 0;
            $stockOutPercentage = $totalStockMovement > 0 ? round(($overviewCounts->total_stock_outs / $totalStockMovement) * 100, 2) : 0;

            // ==================== SALES OVERVIEW CHART (line chart data) ====================
            $monthlySales = Sales::query()
                ->selectRaw("TO_CHAR(created_at, 'Mon') as month, TO_CHAR(created_at, 'MM') as month_num, SUM(total) as total")
                ->where('created_at', '>=', now()->subMonths(11))
                ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM'), TO_CHAR(created_at, 'Mon')")
                ->orderByRaw("TO_CHAR(created_at, 'YYYY-MM')")
                ->get();

            // ==================== STOCK IN vs STOCK OUT CHART (bar chart data) ====================
            $monthlyStock = StockTransaction::query()
                ->selectRaw("TO_CHAR(created_at, 'Mon') as month, TO_CHAR(created_at, 'MM') as month_num, type, SUM(quantity) as total_quantity")
                ->whereIn('type', ['PURCHASE', 'SALE'])
                ->where('created_at', '>=', now()->subMonths(11))
                ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM'), TO_CHAR(created_at, 'Mon'), type")
                ->orderByRaw("TO_CHAR(created_at, 'YYYY-MM')")
                ->get();

            // ==================== CUSTOMER GROWTH CHART (line chart data) ====================
            $monthlyCustomers = Customers::query()
                ->selectRaw("TO_CHAR(created_at, 'Mon') as month, TO_CHAR(created_at, 'MM') as month_num, COUNT(*) as total")
                ->where('created_at', '>=', now()->subMonths(11))
                ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM'), TO_CHAR(created_at, 'Mon')")
                ->orderByRaw("TO_CHAR(created_at, 'YYYY-MM')")
                ->get();

            // ==================== RECENT SALES & TOP PRODUCTS ====================
            $recentSales = Sales::select('id', 'customer_id', 'sold_by', 'total', 'payment_status', 'created_at')
                ->with(['customer:id,name'])
                ->latest()
                ->take(5)
                ->get();

            $topProducts = Products::select('id', 'name', 'price')
                ->orderBy('price', 'desc')
                ->take(5)
                ->get()
                ->map(function ($p) {
                    $p->stock_quantity = $p->total_stock;
                    return $p;
                });

            // ==================== HELPER ====================
            $pctChange = fn($current, $last) => $last > 0
                ? round((($current - $last) / $last) * 100, 2)
                : ($current > 0 ? 100 : 0);

            // ==================== BUILD SALES OVERVIEW CHART DATA ====================
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $salesOverview = [];
            $monthMap = [];

            foreach ($monthlySales as $sale) {
                $m = (int)$sale->month_num;
                $monthMap[$m] = $sale->total;
            }
            foreach ($months as $idx => $monthName) {
                $salesOverview[] = [
                    'month' => $monthName,
                    'revenue' => (float) ($monthMap[$idx + 1] ?? 0),
                ];
            }

            // ==================== BUILD STOCK MOVEMENT CHART DATA ====================
            $stockInMap = [];
            $stockOutMap = [];
            foreach ($monthlyStock as $stock) {
                $m = (int)$stock->month_num;
                if ($stock->type === 'PURCHASE') {
                    $stockInMap[$m] = (float) $stock->total_quantity;
                } elseif ($stock->type === 'SALE') {
                    $stockOutMap[$m] = (float) $stock->total_quantity;
                }
            }
            $stockMovement = [];
            foreach ($months as $idx => $monthName) {
                $stockMovement[] = [
                    'month' => $monthName,
                    'stock_in' => $stockInMap[$idx + 1] ?? 0,
                    'stock_out' => $stockOutMap[$idx + 1] ?? 0,
                ];
            }

            // ==================== BUILD CUSTOMER GROWTH CHART DATA ====================
            $customerGrowth = [];
            $customerMap = [];

            foreach ($monthlyCustomers as $c) {
                $m = (int)$c->month_num;
                $customerMap[$m] = (int) $c->total;
            }
            foreach ($months as $idx => $monthName) {
                $customerGrowth[] = [
                    'month' => $monthName,
                    'new_customers' => $customerMap[$idx + 1] ?? 0,
                ];
            }

            // ==================== PRODUCT DISTRIBUTION (by category - pie chart data) ====================
            $categoryDistribution = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select('categories.name', DB::raw('COUNT(products.id) as count'))
                ->groupBy('categories.name')
                ->get()
                ->map(function ($row) {
                    return [
                        'category' => $row->name,
                        'count' => (int) $row->count,
                    ];
                })
                ->toArray();

            // ==================== ASSEMBLE RESPONSE ====================
            $data = [
                'overview' => [
                    'total_products'             => $overviewCounts->total_products,
                    'total_customers'            => $overviewCounts->total_customers,
                    'total_suppliers'            => $overviewCounts->total_suppliers,
                    'total_users'                => $overviewCounts->total_users,
                    'total_sales'                => $overviewCounts->total_sales,
                    'total_revenue'              => $overviewCounts->total_revenue,
                    'total_stock_ins'            => $overviewCounts->total_stock_ins,
                    'total_stock_outs'           => $overviewCounts->total_stock_outs,
                    'low_stock_count'            => $overviewCounts->low_stock_count,
                    'out_of_stock_count'         => $overviewCounts->out_of_stock_count,
                    // Revenue
                    'monthly_revenue'            => $revenueComparison->current_month,
                    'previous_month_revenue'     => $revenueComparison->last_month,
                    'revenue_percentage_change'  => $pctChange($revenueComparison->current_month, $revenueComparison->last_month),
                    // Customers
                    'current_month_customers'    => $customersComparison->current_month,
                    'last_month_customers'       => $customersComparison->last_month,
                    'customers_percentage_change'=> $pctChange($customersComparison->current_month, $customersComparison->last_month),
                    // Products
                    'current_month_products'     => $productsComparison->current_month,
                    'last_month_products'        => $productsComparison->last_month,
                    'products_percentage_change' => $pctChange($productsComparison->current_month, $productsComparison->last_month),
                    // Suppliers
                    'current_month_suppliers'    => $suppliersComparison->current_month,
                    'last_month_suppliers'       => $suppliersComparison->last_month,
                    'suppliers_percentage_change'=> $pctChange($suppliersComparison->current_month, $suppliersComparison->last_month),
                    // Sales
                    'current_month_sales'        => $salesComparison->current_month,
                    'last_month_sales'           => $salesComparison->last_month,
                    'sales_percentage_change'    => $pctChange($salesComparison->current_month, $salesComparison->last_month),
                    // Stock In
                    'current_month_stock_ins'    => $stockInComparison->current_month,
                    'last_month_stock_ins'       => $stockInComparison->last_month,
                    'stock_ins_percentage_change'=> $pctChange($stockInComparison->current_month, $stockInComparison->last_month),
                    // Stock Out
                    'current_month_stock_outs'    => $stockOutComparison->current_month,
                    'last_month_stock_outs'       => $stockOutComparison->last_month,
                    'stock_outs_percentage_change'=> $pctChange($stockOutComparison->current_month, $stockOutComparison->last_month),
                ],
                'percentages' => [
                    'customers'    => $customerPercentages,
                    'products'     => $productPercentages,
                    'stock_in_out' => [
                        'stock_in_percentage'  => $stockInPercentage,
                        'stock_out_percentage' => $stockOutPercentage,
                        'total_stock_ins'      => $overviewCounts->total_stock_ins,
                        'total_stock_outs'     => $overviewCounts->total_stock_outs,
                    ],
                    'suppliers' => $supplierPercentages,
                ],
                'month_comparison' => [
                    'revenue'      => ['current_month' => $revenueComparison->current_month,   'last_month' => $revenueComparison->last_month,   'change_percentage' => $pctChange($revenueComparison->current_month,   $revenueComparison->last_month)],
                    'stock_in'     => ['current_month' => $stockInComparison->current_month,   'last_month' => $stockInComparison->last_month,   'change_percentage' => $pctChange($stockInComparison->current_month,   $stockInComparison->last_month)],
                    'stock_out'    => ['current_month' => $stockOutComparison->current_month,  'last_month' => $stockOutComparison->last_month,  'change_percentage' => $pctChange($stockOutComparison->current_month,  $stockOutComparison->last_month)],
                    'sales_count'  => ['current_month' => $salesComparison->current_month,     'last_month' => $salesComparison->last_month,     'change_percentage' => $pctChange($salesComparison->current_month,     $salesComparison->last_month)],
                    'new_customers'=> ['current_month' => $customersComparison->current_month, 'last_month' => $customersComparison->last_month, 'change_percentage' => $pctChange($customersComparison->current_month, $customersComparison->last_month)],
                    'new_suppliers'=> ['current_month' => $suppliersComparison->current_month, 'last_month' => $suppliersComparison->last_month, 'change_percentage' => $pctChange($suppliersComparison->current_month, $suppliersComparison->last_month)],
                    'new_products' => ['current_month' => $productsComparison->current_month,  'last_month' => $productsComparison->last_month,  'change_percentage' => $pctChange($productsComparison->current_month,  $productsComparison->last_month)],
                ],
                'recent_sales' => $recentSales,
                'top_products' => $topProducts,
                // Chart data
                'sales_overview'       => $salesOverview,
                'stock_movement'       => $stockMovement,
                'customer_growth'      => $customerGrowth,
                'category_distribution'=> $categoryDistribution,
            ];

            Cache::put($cacheKey, $data, $cacheTTL);

            return ResponseHelper::success('Dashboard data retrieved successfully', $data);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}