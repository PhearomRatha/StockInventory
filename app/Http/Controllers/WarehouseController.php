<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreWarehouseRequest;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Warehouse::class);

        try {
            $perPage = min($request->query('per_page', 15), 100);

            $warehouses = Warehouse::with('creator:id,name')
                ->when($request->query('active_only'), fn ($q) => $q->where('status', true))
                ->latest()
                ->paginate($perPage);

            return ResponseHelper::success('Warehouses retrieved successfully', $warehouses);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $warehouse = Warehouse::with('creator:id,name')->findOrFail($id);
            $this->authorize('view', $warehouse);

            return ResponseHelper::success('Warehouse retrieved successfully', $warehouse);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function stock(Request $request, $id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            $this->authorize('view', $warehouse);

            $stock = $this->inventoryService->getWarehouseStock(
                (int) $id,
                min((int) $request->query('per_page', 50), 100)
            );

            return ResponseHelper::success('Warehouse stock retrieved successfully', [
                'warehouse' => $warehouse->only(['id', 'name', 'code']),
                'stock' => $stock,
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreWarehouseRequest $request)
    {
        try {
            $this->authorize('create', Warehouse::class);

            $warehouse = Warehouse::create([
                ...$request->validated(),
                'status' => $request->boolean('status', true),
                'created_by' => $request->user()->id,
            ]);

            ActivityLogHelper::logCreated('warehouses', "Created warehouse {$warehouse->name}");

            return ResponseHelper::success('Warehouse created successfully', $warehouse, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(StoreWarehouseRequest $request, $id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            $this->authorize('update', $warehouse);

            $warehouse->update($request->validated());

            ActivityLogHelper::logUpdated('warehouses', "Updated warehouse {$warehouse->name}");

            return ResponseHelper::success('Warehouse updated successfully', $warehouse);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            $this->authorize('delete', $warehouse);

            if ($warehouse->warehouseProducts()->where('quantity', '>', 0)->exists()) {
                return ResponseHelper::error('Cannot delete warehouse with stock on hand.', 422);
            }

            $warehouse->delete();

            ActivityLogHelper::logDeleted('warehouses', "Deleted warehouse {$warehouse->name}");

            return ResponseHelper::success('Warehouse deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
