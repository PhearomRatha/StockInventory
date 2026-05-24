<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\StockTransaction;
use Illuminate\Http\Request;

class StockTransactionsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = min($request->query('per_page', 15), 100);

            $query = StockTransaction::with([
                'product:id,name,sku',
                'warehouse:id,name,code',
                'user:id,name',
            ])->latest();

            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->query('warehouse_id'));
            }

            if ($request->filled('type')) {
                $query->where('type', $request->query('type'));
            }

            return ResponseHelper::success(
                'Stock transactions retrieved successfully',
                $query->paginate($perPage)
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $transaction = StockTransaction::with([
                'product',
                'warehouse',
                'user:id,name',
            ])->findOrFail($id);

            return ResponseHelper::success('Stock transaction retrieved successfully', $transaction);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function overview()
    {
        try {
            $totalStockIns = StockTransaction::where('type', 'PURCHASE')->sum('quantity');
            $totalStockOuts = StockTransaction::where('type', 'SALE')->sum('quantity');

            return ResponseHelper::success('Stock overview retrieved successfully', [
                'total_stock_ins' => $totalStockIns,
                'total_stock_outs' => $totalStockOuts,
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
