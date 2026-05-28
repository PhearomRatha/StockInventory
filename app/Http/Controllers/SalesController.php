<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreSaleRequest;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function __construct(protected SaleService $saleService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Sale::class);
        try {
            $perPage = min((int) $request->query('per_page', 15), 100);

            return ResponseHelper::success(
                'Sales retrieved successfully',
                $this->saleService->paginate($perPage)
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorize('view', Sale::class);
        try {
            return ResponseHelper::success(
                'Sale details retrieved successfully',
                $this->saleService->find((int) $id)
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreSaleRequest $request)
    {
        $this->authorize('create', Sale::class);
        try {
            $sale = $this->saleService->create(
                $request->validated(),
                $request->user()->id
            );

            return ResponseHelper::success('Sale created successfully', $sale, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function checkoutSale(StoreSaleRequest $request)
    {
        $this->authorize('create', Sale::class);
        try {
            $sale = $this->saleService->create(
                $request->validated(),
                $request->user()->id,
                'PAID'
            );

            return ResponseHelper::success('Sale checked out successfully', $sale, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function checkout(StoreSaleRequest $request)
    {
        return $this->checkoutSale($request);
    }

    public function searchProducts(Request $request)
    {
        $this->authorize('viewAny', Product::class);
        try {
            $perPage = min((int) $request->query('per_page', 20), 100);

            return ResponseHelper::success(
                'Products retrieved',
                $this->saleService->searchProducts($request->query('search'), $perPage)
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function searchCustomers(Request $request)
    {
        $this->authorize('viewAny', Customer::class);
        try {
            $perPage = min((int) $request->query('per_page', 20), 100);

            return ResponseHelper::success(
                'Customers retrieved',
                $this->saleService->searchCustomers($request->query('search'), $perPage)
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function getSalesData(Request $request)
    {
        return $this->index($request);
    }

    public function dashboard(Request $request)
    {
        return $this->index($request);
    }

    public function verifyPayment()
    {
        return ResponseHelper::success('Payment verification is handled by the payments endpoint', [
            'verified' => false,
        ]);
    }
}
