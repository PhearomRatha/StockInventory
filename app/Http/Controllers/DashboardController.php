<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\Products;
use App\Models\Sales;
use App\Models\SaleItem;
use App\Models\Stock_ins;
use App\Models\Stock_outs;
use App\Models\Suppliers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /* ============================
       DASHBOARD SUMMARY DATA
       ============================ */

    // Total Products
    public function totalProduct()
    {
        try {
            return Cache::remember('dashboard_total_products', 10, function () {
                $totalThisMonth = Products::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->count();

                $totalLastMonth = Products::whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ])->count();

                $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

                return response()->json([
                    'status' => 200,
                    'total_this_month' => $totalThisMonth,
                    'total_last_month' => $totalLastMonth,
                    'percent_change' => $percentChange
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Customers
    public function totalCustomer()
    {
        try {
            return Cache::remember('dashboard_total_customers', 10, function () {
                $totalThisMonth = Customers::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->count();

                $totalLastMonth = Customers::whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ])->count();

                $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

                return response()->json([
                    'status' => 200,
                    'total_this_month' => $totalThisMonth,
                    'total_last_month' => $totalLastMonth,
                    'percent_change' => $percentChange
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Sales
    public function totalSales()
    {
        try {
            return Cache::remember('dashboard_total_sales', 5, function () {
                $totalThisMonth = Sales::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->sum('total_amount');

                $totalLastMonth = Sales::whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ])->sum('total_amount');

                $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

                return response()->json([
                    'status' => 200,
                    'total_this_month' => $totalThisMonth,
                    'total_last_month' => $totalLastMonth,
                    'percent_change' => $percentChange
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Suppliers
    public function totalSupplier()
    {
        try {
            return Cache::remember('dashboard_total_suppliers', 10, function () {
                $totalThisMonth = Suppliers::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->count();

                $totalLastMonth = Suppliers::whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ])->count();

                $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

                return response()->json([
                    'status' => 200,
                    'total_this_month' => $totalThisMonth,
                    'total_last_month' => $totalLastMonth,
                    'percent_change' => $percentChange
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Stock-In
    public function totalStockIn()
    {
        try {
            return Cache::remember('dashboard_total_stock_in', 5, function () {
                $totalThisMonth = Stock_ins::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->sum('quantity');

                $totalLastMonth = Stock_ins::whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ])->sum('quantity');

                $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

                return response()->json([
                    'status' => 200,
                    'total_this_month' => $totalThisMonth,
                    'total_last_month' => $totalLastMonth,
                    'percent_change' => $percentChange
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Stock-Out
    public function totalStockOut()
    {
        try {
            return Cache::remember('dashboard_total_stock_out', 5, function () {
                $totalThisMonth = Stock_outs::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->sum('quantity') + SaleItem::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->sum('quantity');

                $totalLastMonth = Stock_outs::whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ])->sum('quantity') + SaleItem::whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ])->sum('quantity');

                $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

                return response()->json([
                    'status' => 200,
                    'total_this_month' => $totalThisMonth,
                    'total_last_month' => $totalLastMonth,
                    'percent_change' => $percentChange
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    /* ============================
       STOCK ALERT & SUMMARY
       ============================ */

    // Stock Alert
    public function stockAlert()
    {
        try {
            return Cache::remember('dashboard_stock_alert', 5, function () {
                $alerts = [];

                $products = Products::all();

                foreach ($products as $product) {
                    if ($product->stock_quantity == 0) {
                        $alerts[] = "{$product->name} is OUT OF STOCK";
                    } elseif ($product->reorder_level && $product->stock_quantity <= ($product->reorder_level * 0.3)) {
                        $alerts[] = "{$product->name} stock is below 30%";
                    }
                }

                return response()->json([
                    'status' => 200,
                    'alerts' => $alerts
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Stock-In Summary
    public function stockInSummary()
    {
        try {
            return Cache::remember('dashboard_stock_in_summary', 10, function () {
                $today = Carbon::today();

                $totalToday = Stock_ins::whereDate('created_at', $today)->sum('quantity');
                $totalThisMonth = Stock_ins::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->sum('quantity');
                $totalLastMonth = Stock_ins::whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ])->sum('quantity');
                $totalYear = Stock_ins::whereBetween('created_at', [
                    Carbon::now()->startOfYear(),
                    Carbon::now()
                ])->sum('quantity');

                $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

                return response()->json([
                    'status' => 200,
                    'total_today' => $totalToday,
                    'total_month' => $totalThisMonth,
                    'total_last_month' => $totalLastMonth,
                    'total_year' => $totalYear,
                    'percent_change' => $percentChange,
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    /* ============================
       CHART DATA
       ============================ */

    // Sales Trend Chart Data
    public function salesTrend()
    {
        try {
            return Cache::remember('dashboard_sales_trend', 20, function () {
                $sales = Sales::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total_amount) as total_sales')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

                $sales = $sales->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'total_sales' => (float)$item->total_sales
                    ];
                });

                return response()->json([
                    'status' => 200,
                    'message' => 'Sales trend retrieved successfully',
                    'data' => $sales
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Stock Level Chart Data
    public function stockLevels()
    {
        try {
            return Cache::remember('dashboard_stock_levels', 7, function () {
                $products = Products::select('name as product', 'stock_quantity as stock')->get();

                $products = $products->map(function ($item) {
                    return [
                        'product' => $item->product,
                        'stock' => (int)$item->stock
                    ];
                });

                return response()->json([
                    'status' => 200,
                    'data' => $products
                ]);
            });
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    /* ============================
       HELPER FUNCTIONS
       ============================ */

    private function calculatePercentChange($current, $previous)
    {
        if ($previous == 0 && $current > 0) return 100;
        if ($previous == 0 && $current == 0) return 0;

        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function jsonError($e)
    {
        return response()->json([
            'status' => 500,
            'error' => $e->getMessage()
        ]);
    }
}
