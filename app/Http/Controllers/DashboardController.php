<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\Products;
use App\Models\Sales;
use App\Models\Stock_ins;
use App\Models\Stock_outs;
use App\Models\Suppliers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Total Products
    public function totalProduct()
    {
        try {
            $totalThisMonth = Products::whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()
            ])->count();

            $totalLastMonth = Products::whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()
            ])->count();

            $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

            return response()->json([
                'status' => 200,
                'total_this_month' => $totalThisMonth,
                'total_last_month' => $totalLastMonth,
                'percent_change' => $percentChange
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Customers
    public function totalCustomer()
    {
        try {
            $totalThisMonth = Customers::whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()
            ])->count();

            $totalLastMonth = Customers::whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()
            ])->count();

            $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

            return response()->json([
                'status' => 200,
                'total_this_month' => $totalThisMonth,
                'total_last_month' => $totalLastMonth,
                'percent_change' => $percentChange
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Sales
    public function totalSales()
    {
        try {
            $totalThisMonth = Sales::whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()
            ])->count();

            $totalLastMonth = Sales::whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()
            ])->count();

            $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

            return response()->json([
                'status' => 200,
                'total_this_month' => $totalThisMonth,
                'total_last_month' => $totalLastMonth,
                'percent_change' => $percentChange
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Suppliers
    public function totalSupplier()
    {
        try {
            $totalThisMonth = Suppliers::whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()
            ])->count();

            $totalLastMonth = Suppliers::whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()
            ])->count();

            $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

            return response()->json([
                'status' => 200,
                'total_this_month' => $totalThisMonth,
                'total_last_month' => $totalLastMonth,
                'percent_change' => $percentChange
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Stock-In
    public function totalStockIn()
    {
        try {
            $totalThisMonth = Stock_ins::whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()
            ])->sum('quantity');

            $totalLastMonth = Stock_ins::whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()
            ])->sum('quantity');

            $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

            return response()->json([
                'status' => 200,
                'total_this_month' => $totalThisMonth,
                'total_last_month' => $totalLastMonth,
                'percent_change' => $percentChange
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Total Stock-Out
    public function totalStockOut()
    {
        try {
            $totalThisMonth = Stock_outs::whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()
            ])->sum('quantity');

            $totalLastMonth = Stock_outs::whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()
            ])->sum('quantity');

            $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

            return response()->json([
                'status' => 200,
                'total_this_month' => $totalThisMonth,
                'total_last_month' => $totalLastMonth,
                'percent_change' => $percentChange
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Stock-In Summary (Today, Month, Year)
    public function stockInSummary()
    {
        try {
            $today = Carbon::today();
            $totalToday = Stock_ins::whereDate('created_at', $today)->sum('quantity');
            $totalThisMonth = Stock_ins::whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()
            ])->sum('quantity');
            $totalLastMonth = Stock_ins::whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()
            ])->sum('quantity');
            $totalYear = Stock_ins::whereBetween('created_at', [
                Carbon::now()->startOfYear(), Carbon::now()
            ])->sum('quantity');

            $percentChange = $this->calculatePercentChange($totalThisMonth, $totalLastMonth);

            return response()->json([
                'status' => 200,
                'total_today' => $totalToday,
                'total_month' => $totalThisMonth,
                'total_last_month' => $totalLastMonth,
                'total_year' => $totalYear,
                'percent_change' => $percentChange
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Stock Alert
    public function stockAlert()
    {
        try {
            $alerts = [];
            $products = Products::all();

            foreach ($products as $product) {
                if ($product->quantity == 0) {
                    $alerts[] = "{$product->name} is out of stock";
                } elseif ($product->quantity <= ($product->max_quantity * 0.3)) {
                    $alerts[] = "{$product->name} stock is lower than 30%";
                }
            }

            return response()->json([
                'status' => 200,
                'alerts' => $alerts
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e);
        }
    }

    // Helper: Calculate percent change
    private function calculatePercentChange($current, $previous)
    {
        if ($previous == 0 && $current > 0) return 100;
        if ($previous == 0 && $current == 0) return 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }

    // Helper: JSON Error Response
    private function jsonError($e)
    {
        return response()->json([
            'status' => 500,
            'error' => $e->getMessage()
        ]);
    }


public function salesTrend()
{
    $sales = Sales::select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('SUM(total_amount) as total_sales')
    )
    ->groupBy('date')
    ->orderBy('date')
    ->get();

    // Convert total_sales to number
    $sales = $sales->map(function($item) {
        return [
            'date' => $item->date,
            'total_sales' => (float) $item->total_sales
        ];
    });

    return response()->json([
        'status' => 200,
        'message' => 'Sales trend retrieved successfully',
        'data' => $sales
    ]);
}
public function stockLevels()
{
    $products = Products::select('name as product', 'quantity as stock')->get();

    // Optional: highlight low stock for charts
    $products = $products->map(function($item) {
        return [
            'product' => $item->product,
            'stock' => (int) $item->stock,
        ];
    });

    return response()->json($products);
}

}


