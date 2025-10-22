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

class DashboardController extends Controller
{
    // Total customers
public function totalCustomer()
{
    try {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Count customers created this month and last month
        $totalThisMonth = Customers::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
        $totalLastMonth = Customers::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // Calculate percent change (relative version like your supplier example)
        if ($totalLastMonth == 0 && $totalThisMonth > 0) {
            $percentChange = 100;
        } elseif ($totalLastMonth == 0 && $totalThisMonth == 0) {
            $percentChange = 0;
        } else {
            $percentChange = (($totalThisMonth - $totalLastMonth) / $totalThisMonth) * 100;
        }

        // Round to whole number
        $percentChange = round($percentChange);

        return response()->json([
            'status' => 200,
            'message' => 'Total customers retrieved successfully',
            'total_this_month' => $totalThisMonth,
            'total_last_month' => $totalLastMonth,
            'percent_change' => $percentChange . '%'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'error' => $e->getMessage()
        ]);
    }
}
public function totalSales()
{
    try {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Count total sales for this month and last month
        $totalThisMonth = Sales::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
        $totalLastMonth = Sales::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // Calculate percent change (relative version)
        if ($totalLastMonth == 0 && $totalThisMonth > 0) {
            $percentChange = 100;
        } elseif ($totalLastMonth == 0 && $totalThisMonth == 0) {
            $percentChange = 0;
        } else {
            $percentChange = (($totalThisMonth - $totalLastMonth) / $totalThisMonth) * 100;
        }

        // Round to whole number
        $percentChange = round($percentChange);

        return response()->json([
            'status' => 200,
            'message' => 'Total sales retrieved successfully',
            'total_this_month' => $totalThisMonth,
            'total_last_month' => $totalLastMonth,
            'percent_change' => $percentChange . '%'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'error' => $e->getMessage()
        ]);
    }
}


    // Total products
public function totalSupplier()
{
    try {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Count suppliers created this month and last month
        $totalThisMonth = Suppliers::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
        $totalLastMonth = Suppliers::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // Calculate percent change (relative version)
        if ($totalLastMonth == 0 && $totalThisMonth > 0) {
            $percentChange = 100;
        } elseif ($totalLastMonth == 0 && $totalThisMonth == 0) {
            $percentChange = 0;
        } else {
            $percentChange = (($totalThisMonth - $totalLastMonth) / $totalThisMonth) * 100;
        }

        // Round to whole number
        $percentChange = round($percentChange);

        return response()->json([
            'status' => 'success',
            'total_this_month' => $totalThisMonth,
            'total_last_month' => $totalLastMonth,
            'percent_change' => $percentChange . '%'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}


    // Total stock-in
public function totalStockIn()
{
    try {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Count stock-in records for this month and last month
        $totalThisMonth = Stock_ins::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
        $totalLastMonth = Stock_ins::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // Calculate percent change (relative version like your supplier example)
        if ($totalLastMonth == 0 && $totalThisMonth > 0) {
            $percentChange = 100;
        } elseif ($totalLastMonth == 0 && $totalThisMonth == 0) {
            $percentChange = 0;
        } else {
            $percentChange = (($totalThisMonth - $totalLastMonth) / $totalThisMonth) * 100;
        }

        // Round to whole number
        $percentChange = round($percentChange);

        return response()->json([
            'status' => 200,
            'message' => 'Total stock-in records retrieved successfully',
            'total_this_month' => $totalThisMonth,
            'total_last_month' => $totalLastMonth,
            'percent_change' => $percentChange . '%'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'error' => $e->getMessage()
        ]);
    }
}


    // Total stock-out
   public function totalStockOut()
{
    try {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Count stock-out records for this month and last month
        $totalThisMonth = Stock_outs::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
        $totalLastMonth = Stock_outs::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // Calculate percent change (relative version)
        if ($totalLastMonth == 0 && $totalThisMonth > 0) {
            $percentChange = 100;
        } elseif ($totalLastMonth == 0 && $totalThisMonth == 0) {
            $percentChange = 0;
        } else {
            $percentChange = (($totalThisMonth - $totalLastMonth) / $totalThisMonth) * 100;
        }

        // Round to whole number
        $percentChange = round($percentChange);

        return response()->json([
            'status' => 200,
            'message' => 'Total stock-out records retrieved successfully',
            'total_this_month' => $totalThisMonth,
            'total_last_month' => $totalLastMonth,
            'percent_change' => $percentChange . '%'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'error' => $e->getMessage()
        ]);
    }
}


    // Total stock-in for today month and year
   public function stockInSummary()
{
    try {
        $today = Carbon::today();

        // 🟩 This month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $totalThisMonth = Stock_ins::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('quantity');

        // 🟨 Previous month
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        $totalLastMonth = Stock_ins::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('quantity');

        // 🧮 Calculate percentage difference
        if ($totalLastMonth > 0) {
            $percentChange = (($totalThisMonth - $totalLastMonth) / $totalLastMonth) * 100;
        } else {
            // Avoid division by zero
            $percentChange = 0;
        }

        // 🕐 Today
        $totalToday = Stock_ins::whereDate('created_at', $today)->sum('quantity');

        // 🗓️ Year
        $startOfYear = Carbon::now()->startOfYear();
        $totalYear = Stock_ins::whereBetween('created_at', [$startOfYear, Carbon::now()])->sum('quantity');

        return response()->json([
            'status' => 200,
            'message' => 'Stock-in summary retrieved successfully',
            'total_stock_in_today' => $totalToday,
            'total_stock_in_month' => $totalThisMonth,
            'total_stock_in_year' => $totalYear,
            'total_stock_in_last_month' => $totalLastMonth,
            'percent_change_from_last_month' => round($percentChange, 2) . '%'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'error' => $e->getMessage()
        ]);
    }
}



    // Example: Stock alert
    public function stockAlert()
    {
        try {
            $products = Products::all();
            $alerts = [];

            foreach ($products as $product) {
                if ($product->quantity == 0) {
                    $alerts[] = " {$product->name} is out of stock.";
                } elseif ($product->quantity <= ($product->max_quantity * 0.3)) {
                    $alerts[] = "{$product->name} stock is lower than 30%.";
                }
            }

            return response()->json([
                'status' => 200,
                'alerts' => $alerts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ]);
        }
    }
}
