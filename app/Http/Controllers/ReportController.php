<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sales;
use App\Models\Products;
use App\Models\Stock_ins;
use App\Models\Stock_outs;
use App\Models\Activity_logs;
use App\Helpers\ResponseHelper;

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

            $sales = Sales::whereBetween('created_at', [$startDate, $endDate])->get();
            $totalRevenue = $sales->sum('total_amount');
            $totalSales = $sales->count();

            return ResponseHelper::success('Sales report retrieved successfully', [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
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

            $sales = Sales::whereBetween('created_at', [$startDate, $endDate])->get();
            $totalRevenue = $sales->sum('total_amount');

            $stockIns = Stock_ins::whereBetween('created_at', [$startDate, $endDate])->get();
            $totalStockInCost = $stockIns->sum(function ($si) {
                return $si->quantity * ($si->product->price ?? 0);
            });

            return ResponseHelper::success('Financial report retrieved successfully', [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_revenue' => $totalRevenue,
                'total_stock_in_cost' => $totalStockInCost,
                'estimated_profit' => $totalRevenue - $totalStockInCost
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
            $products = Products::all();
            $lowStock = $products->where('stock_quantity', '<', 10)->where('stock_quantity', '>', 0);
            $outOfStock = $products->where('stock_quantity', 0);
            $totalValue = $products->sum(function ($product) {
                return $product->stock_quantity * $product->price;
            });

            return ResponseHelper::success('Stock report retrieved successfully', [
                'total_products' => $products->count(),
                'total_stock_value' => $totalValue,
                'low_stock_count' => $lowStock->count(),
                'out_of_stock_count' => $outOfStock->count(),
                'low_stock_products' => $lowStock,
                'out_of_stock_products' => $outOfStock
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get activity log report
     */
    public function activityLogReport(Request $request)
    {
        try {
            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();
            $type = $request->type;

            $query = Activity_logs::whereBetween('created_at', [$startDate, $endDate]);
            if ($type) {
                $query->where('type', $type);
            }
            $logs = $query->latest()->get();

            return ResponseHelper::success('Activity log report retrieved successfully', [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_activities' => $logs->count(),
                'activities' => $logs
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
