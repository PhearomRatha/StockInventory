<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Products;
use App\Models\Sales;
use App\Models\Stock_ins;
use App\Models\Stock_outs;
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
            $totalProducts = Products::count();
            $totalUsers = User::count();
            $totalSales = Sales::count();
            $totalRevenue = Sales::sum('total_amount');

            $lowStock = Products::where('is_low_stock', true)->count();
            $outOfStock = Products::where('stock_quantity', 0)->count();

            $recentSales = Sales::with(['customer'])->latest()->take(5)->get();
            $topProducts = Products::orderBy('stock_quantity', 'desc')->take(5)->get();

            $monthlySales = Sales::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount');

            return ResponseHelper::success('Dashboard data retrieved successfully', [
                'overview' => [
                    'total_products' => $totalProducts,
                    'total_users' => $totalUsers,
                    'total_sales' => $totalSales,
                    'total_revenue' => $totalRevenue,
                    'monthly_revenue' => $monthlySales,
                    'low_stock_count' => $lowStock,
                    'out_of_stock_count' => $outOfStock
                ],
                'recent_sales' => $recentSales,
                'top_products' => $topProducts
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
