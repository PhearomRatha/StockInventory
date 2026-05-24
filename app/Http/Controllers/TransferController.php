<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreTransferRequest;
use App\Models\Transfer;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Transfer::class);

        try {
            $perPage = min($request->query('per_page', 15), 100);

            $query = Transfer::with([
                'fromWarehouse:id,name,code',
                'toWarehouse:id,name,code',
                'createdBy:id,name',
                'approvedBy:id,name',
                'items.product:id,name,sku',
            ])->latest();

            if ($request->filled('status')) {
                $query->where('status', strtoupper($request->query('status')));
            }

            return ResponseHelper::success('Transfers retrieved successfully', $query->paginate($perPage));
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $transfer = Transfer::with([
                'fromWarehouse',
                'toWarehouse',
                'createdBy:id,name',
                'approvedBy:id,name',
                'items.product',
            ])->findOrFail($id);

            $this->authorize('view', $transfer);

            return ResponseHelper::success('Transfer retrieved successfully', $transfer);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreTransferRequest $request)
    {
        try {
            $this->authorize('create', Transfer::class);

            $validated = $request->validated();

            $transfer = $this->inventoryService->createTransfer(
                (int) $validated['from_warehouse_id'],
                (int) $validated['to_warehouse_id'],
                $validated['items'],
                $validated['notes'] ?? null,
                $request->user()->id
            );

            return ResponseHelper::success('Transfer created successfully', $transfer, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $transfer = Transfer::findOrFail($id);
            $this->authorize('approve', $transfer);

            $transfer = $this->inventoryService->approveTransfer((int) $id, $request->user()->id);

            return ResponseHelper::success('Transfer approved successfully', $transfer);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $transfer = Transfer::findOrFail($id);
            $this->authorize('reject', $transfer);

            $transfer = $this->inventoryService->rejectTransfer((int) $id, $request->user()->id);

            return ResponseHelper::success('Transfer rejected successfully', $transfer);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function complete(Request $request, $id)
    {
        try {
            $transfer = Transfer::findOrFail($id);
            $this->authorize('complete', $transfer);

            $transfer = $this->inventoryService->completeTransfer((int) $id, $request->user()->id);

            return ResponseHelper::success('Transfer completed successfully', $transfer);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
