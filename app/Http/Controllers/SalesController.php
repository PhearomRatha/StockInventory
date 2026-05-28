<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\ResponseHelper;
use App\Models\Customers as Customer;
use App\Models\Products as Product;
use App\Models\Sales as Sale;
use App\Http\Requests\StoreSaleRequest;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Sale::class);
        try {
            $perPage = min($request->query('per_page', 15), 100);

              $sales = Sale::select(
                  'id',
                  'customer_id',
                  'warehouse_id',
                  'sold_by',
                  'invoice_number',
                  'subtotal',
                  'discount',
                  'tax',
                  'total',
                  'payment_status',
                  'payment_method',
                  'notes',
                  'sold_at',
                  'created_at'
              )
                 ->with([
                     'customer:id,name',
                     'soldBy:id,name',
                     'warehouse:id,name,code',
                 ])
                 ->latest()
                 ->paginate($perPage);

            return ResponseHelper::success('Sales retrieved successfully', $sales);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorize('view', Sale::class);
        try {
            $sale = Sale::with([
                'customer:id,name,email',
                'soldBy:id,name',
                'warehouse:id,name,code',
                'saleItems.product:id,name,price,sku',
            ])
                ->findOrFail($id);

            return ResponseHelper::success('Sale details retrieved successfully', $sale);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreSaleRequest $request)
    {
        $this->authorize('create', Sale::class);
        try {
            $user = $request->user();
            $validated = $request->validated();
            $warehouseId = (int) $validated['warehouse_id'];

            $sale = DB::transaction(function () use ($validated, $user, $warehouseId) {
                $productIds = collect($validated['items'])->pluck('product_id')->unique();
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                $subtotal = 0;
                $saleItemsData = [];

                foreach ($validated['items'] as $item) {
                    $product = $products->get($item['product_id']);
                    if (! $product) {
                        throw new \Exception('Product not found.');
                    }

                    $qty = (float) $item['quantity'];
                    $unitPrice = (float) $product->price;
                    $itemTotal = $unitPrice * $qty;
                    $subtotal += $itemTotal;

                    $saleItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount' => 0,
                        'total' => $itemTotal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Use InventoryService: will check, decrease warehouse_product qty, create STOCK_TRANSACTION type=SALE
                    $this->inventoryService->decreaseStock(
                        $product->id,
                        $qty,
                        $warehouseId,
                        \App\Models\StockTransaction::TYPE_SALE,
                        'Sale deduction',
                        $user->id,
                        null,
                        'sale'
                    );
                }

                $invoiceNumber = 'INV-' . now()->format('YmdHis') . '-' . rand(1000, 9999);
                $discount = (float) ($validated['discount'] ?? 0);
                $tax = (float) ($validated['tax'] ?? 0);
                $total = $subtotal - $discount + $tax;

                $sale = Sale::create([
                    'customer_id' => $validated['customer_id'] ?? null,
                    'warehouse_id' => $warehouseId,
                    'invoice_number' => $invoiceNumber,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total' => $total,
                    'payment_status' => 'UNPAID',
                    'payment_method' => $validated['payment_method'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'sold_by' => $user->id,
                    'sold_at' => now(),
                ]);

                $sale->saleItems()->createMany($saleItemsData);

                ActivityLogHelper::log('sale', "Created Sale #{$sale->id} in warehouse {$warehouseId}");

                return $sale->load('saleItems.product:id,name', 'customer:id,name', 'soldBy:id,name', 'warehouse:id,name');
            });

            return ResponseHelper::success('Sale created successfully', $sale, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function checkout(Request $request)
    {
        $this->authorize('create', Sale::class);
        try {
            $request->merge(['status' => 'completed', 'payment_status' => 'paid']);

            return $this->store($request);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function searchProducts(Request $request)
    {
        $this->authorize('viewAny', Product::class);
        try {
            $search = $request->query('search');

            // SQLite doesn't support HAVING with withSum subqueries
            // Use a subquery join instead
            $subQuery = DB::table('warehouse_products')
                ->select('product_id', DB::raw('SUM(quantity) as stock_quantity'))
                ->groupBy('product_id')
                ->havingRaw('SUM(quantity) > 0');

            $products = Product::select('products.id', 'products.name', 'products.price', 'wq.stock_quantity')
                ->leftJoinSub($subQuery, 'wq', 'wq.product_id', '=', 'products.id')
                ->when($search, function ($query) use ($search) {
                    $query->where('products.name', 'like', "%{$search}%");
                })
                ->orderBy('products.name')
                ->paginate(20);

            return ResponseHelper::success('Products retrieved', $products);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function searchCustomers(Request $request)
    {
        $this->authorize('viewAny', Customer::class);
        try {
            $search = $request->query('search');

            $customers = Customer::select('id', 'name')
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->paginate(20);

            return ResponseHelper::success('Customers retrieved', $customers);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function getDashboard()
    {
        $this->authorize('viewAny', Sale::class);
        try {
            $today = now()->toDateString();
            $monthStart = now()->startOfMonth();

            $sales = Sale::selectRaw('COUNT(*) as count, SUM(total) as total')
                ->where('sold_at', '>=', $monthStart)
                ->first();

            return ResponseHelper::success('Sales dashboard retrieved', [
                'month_sales' => $sales->count ?? 0,
                'month_revenue' => (float) ($sales->total ?? 0),
                'today_sales' => Sale::whereDate('sold_at', $today)->count(),
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sale = Sale::findOrFail($id);
            $this->authorize('update', $sale);

            $validated = $request->validate([
                'customer_id' => 'sometimes|nullable|exists:customers,id',
                'notes' => 'nullable|string',
                'payment_status' => 'sometimes|in:UNPAID,PAID,REFUNDED',
            ]);

            $sale->update($validated);

            return ResponseHelper::success('Sale updated successfully', $sale);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $sale = Sale::findOrFail($id);
            $this->authorize('delete', $sale);

            $sale->delete();

            return ResponseHelper::success('Sale deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
