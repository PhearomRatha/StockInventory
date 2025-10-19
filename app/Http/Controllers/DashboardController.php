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
            $totalCus = Customers::count();

            return response()->json([
                'status' => 200,
                'message' => 'Total customers retrieved successfully',
                'total_customers' => $totalCus
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
            $total_Sup = Suppliers::count();

            return response()->json([
                'status' => 200,
                'message' => 'Total Supplier retrieved successfully',
                'total_supplier' => $total_Sup
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Total sales
    public function totalSale()
    {
        try {
            $totalSale = Sales::count();

            return response()->json([
                'status' => 200,
                'message' => 'Total sales retrieved successfully',
                'total_sales' => $totalSale
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Total stock-in
    public function totalStockIn()
    {
        try {
            $totalStockIn = Stock_ins::count();

            return response()->json([
                'status' => 200,
                'message' => 'Total stock-in records retrieved successfully',
                'total_stock_in' => $totalStockIn
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
            $totalStockOut = Stock_outs::count();

            return response()->json([
                'status' => 200,
                'message' => 'Total stock-out records retrieved successfully',
                'total_stock_out' => $totalStockOut
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
        $totalToday = Stock_ins::whereDate('created_at', $today)->sum('quantity');

        $startOfMonth = Carbon::now()->startOfMonth();
        $totalMonth = Stock_ins::whereBetween('created_at', [$startOfMonth, Carbon::now()])->sum('quantity');

        $startOfYear = Carbon::now()->startOfYear();
        $totalYear = Stock_ins::whereBetween('created_at', [$startOfYear, Carbon::now()])->sum('quantity');

        return response()->json([
            'status' => 200,
            'message' => 'Stock-in summary retrieved successfully',
            'total_stock_in_today' => $totalToday,
            'total_stock_in_month' => $totalMonth,
            'total_stock_in_year' => $totalYear
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
