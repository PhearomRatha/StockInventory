<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sales;
use App\Models\Products;
use App\Models\Stock_ins;
use App\Models\Stock_outs;
use App\Models\Activity_logs;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    /**
     * Get sales report
     */
    public function salesReport(Request $request)
    {
        try {
            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();

            // OPTIMIZED: Use single query with aggregation instead of get() + sum()
            $salesData = Sales::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue')
                ->first();

            $sales = Sales::whereBetween('created_at', [$startDate, $endDate])
                ->select('id', 'customer_id', 'sold_by', 'invoice_number', 'total_amount', 'payment_status', 'payment_method', 'status', 'created_at')
                ->with(['customer:id,name'])  // Only fetch customer name
                ->latest()
                ->limit(100) // Add limit to prevent returning too many records
                ->get();

            return ResponseHelper::success('Sales report retrieved successfully', [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_sales' => $salesData->total_sales,
                'total_revenue' => $salesData->total_revenue,
                'sales' => $sales
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get financial report
     */
    public function financialReport(Request $request)
    {
        try {
            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();
            $cacheKey = "financial_report_{$startDate}_{$endDate}";

            // OPTIMIZED: Cache the report for 5 minutes
            $data = Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
                // OPTIMIZED: Single query for sales revenue
                $salesData = Sales::whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue')
                    ->first();

                // OPTIMIZED: Use join to get product price in single query
                $stockInCost = Stock_ins::whereBetween('stock_ins.created_at', [$startDate, $endDate])
                    ->join('products', 'stock_ins.product_id', '=', 'products.id')
                    ->selectRaw('COALESCE(SUM(stock_ins.quantity * products.cost), 0) as total_cost')
                    ->first();

                return [
                    'total_revenue' => $salesData->total_revenue,
                    'total_stock_in_cost' => $stockInCost->total_cost,
                    'estimated_profit' => $salesData->total_revenue - $stockInCost->total_cost
                ];
            });

            return ResponseHelper::success('Financial report retrieved successfully', [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_revenue' => $data['total_revenue'],
                'total_stock_in_cost' => $data['total_stock_in_cost'],
                'estimated_profit' => $data['estimated_profit']
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get stock report
     */
    public function stockReport()
    {
        try {
            $cacheKey = 'stock_report_' . date('Y-m-d');
            
            // OPTIMIZED: Cache the report for 1 hour
            $data = Cache::remember($cacheKey, 3600, function () {
                // OPTIMIZED: Use database queries instead of loading all and filtering in PHP
                $totalProducts = Products::count();
                
                // Get stock values using database aggregation
                $stockValues = Products::selectRaw(
                    'COALESCE(SUM(stock_quantity * price), 0) as total_value'
                )->first();

                // OPTIMIZED: Use WHERE clause at database level
                $lowStockCount = Products::whereBetween('stock_quantity', [1, 9])->count();
                $outOfStockCount = Products::where('stock_quantity', 0)->count();

                // Get actual low stock and out of stock products (limited)
                $lowStock = Products::whereBetween('stock_quantity', [1, 9])
                    ->limit(50)
                    ->get(['id', 'name', 'stock_quantity', 'price']);
                    
                $outOfStock = Products::where('stock_quantity', 0)
                    ->limit(50)
                    ->get(['id', 'name', 'stock_quantity', 'price']);

                return [
                    'total_products' => $totalProducts,
                    'total_stock_value' => $stockValues->total_value,
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock_count' => $outOfStockCount,
                    'low_stock_products' => $lowStock,
                    'out_of_stock_products' => $outOfStock
                ];
            });

            return ResponseHelper::success('Stock report retrieved successfully', $data);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * FIXED: cloned query before count() to avoid query reuse issue
     * Get activity log report
     */
    public function activityLogReport(Request $request)
    {
        try {
            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();
            $type = $request->type;

            // OPTIMIZED: Use count() directly on query instead of getting all then counting
            $query = Activity_logs::whereBetween('created_at', [$startDate, $endDate]);
            
            if ($type) {
                $query->where('type', $type);
            }
            
            // FIXED: Clone query before count to avoid query reuse
            $totalCount = (clone $query)->count();
            
            // OPTIMIZED: Use pagination instead of getting all
            $logs = $query->with(['user'])
                ->latest()
                ->limit(100)
                ->get();

            return ResponseHelper::success('Activity log report retrieved successfully', [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_activities' => $totalCount,
                'activities' => $logs
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
