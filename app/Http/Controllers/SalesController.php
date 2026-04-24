<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\ResponseHelper;
use App\Models\Customers as Customer;
use App\Models\Products as Product;
use App\Models\Sales as Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = min($request->query('per_page', 15), 100);

            $sales = Sale::select(
                'id',
                'customer_id',
                'sold_by',
                'invoice_number',
                'total_amount',
                'payment_status',
                'payment_method',
                'status',
                'created_at'
            )
                ->with([
                    'customer:id,name',
                    'soldBy:id,name',
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
        try {
            $sale = Sale::with([
                'customer:id,name,email',
                'soldBy:id,name',
                'saleItems.product:id,name,price',
            ])
                ->findOrFail($id);

            return ResponseHelper::success('Sale details retrieved successfully', $sale);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreSaleRequest $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validated();

            $sale = DB::transaction(function () use ($validated, $user) {

                $productIds = collect($validated['items'])->pluck('product_id');
                $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

                $totalAmount = 0;
                $saleItems = [];

                foreach ($validated['items'] as $item) {

                    $product = $products->get($item['product_id']);

                    if (! $product) {
                        throw new \Exception('Product not found.');
                    }

                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for {$product->name}");
                    }

                    $itemTotal = $product->price * $item['quantity'];
                    $totalAmount += $itemTotal;

                    $saleItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'total' => $itemTotal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $product->decrement('stock_quantity', $item['quantity']);
                }

                $invoiceNumber = 'INV-'.now()->format('YmdHis').'-'.rand(1000, 9999);

                $sale = Sale::create([
                    'customer_id' => $validated['customer_id'] ?? null,
                    'sold_by' => $user->id,
                    'invoice_number' => $invoiceNumber,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'notes' => $validated['notes'] ?? null,
                ]);

                $sale->saleItems()->createMany($saleItems);

                ActivityLogHelper::log('sale', "Created Sale #{$sale->id}");

                return $sale;
            });

            return ResponseHelper::success('Sale created successfully', $sale, 201);

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function checkout(Request $request)
    {
        try {
            $request->merge(['status' => 'completed', 'payment_status' => 'paid']);

            return $this->store($request);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function searchProducts(Request $request)
    {
        try {
            $search = $request->query('search');

            $products = Product::select('id', 'name', 'price', 'stock_quantity')
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->where('stock_quantity', '>', 0)
                ->paginate(20);

            return ResponseHelper::success('Products retrieved', $products);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function searchCustomers(Request $request)
    {
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
}
