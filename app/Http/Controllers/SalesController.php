<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Sales as Sale;
use App\Models\Products as Product;
use App\Models\SaleItem;
use App\Models\Payments as Payment;
use App\Models\Activity_logs as ActivityLog;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class SalesController extends Controller
{
    // -----------------------------
    // Checkout / create sale
    // -----------------------------
    public function checkoutSale(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'items' => 'required|array|min:1',
                'payment_method' => 'required|string|in:Cash,Bakong',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.discount_percent' => 'nullable|numeric|min:0|max:100'
            ]);

            $customerId = $request->customer_id;
            $soldBy = auth()->id();
            $items = $request->items;
            $paymentMethod = $request->payment_method;

            $productIds = collect($items)->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            // -----------------------------
            // Calculate total & check stock
            // -----------------------------
            $totalAmount = 0;
            foreach ($items as $item) {
                $product = $products[$item['product_id']];
                if ($item['quantity'] > $product->stock_quantity) {
                    return response()->json([
                        'status' => false,
                        'message' => "Insufficient stock for {$product->name}"
                    ], 400);
                }
                $discountPercent = $item['discount_percent'] ?? 0;
                $itemTotal = $product->price * $item['quantity'];
                $discountAmount = $itemTotal * ($discountPercent / 100);
                $totalAmount += $itemTotal - $discountAmount;
            }

            // -----------------------------
            // Create sale (status = unpaid if Bakong)
            // -----------------------------
            $sale = Sale::create([
                'customer_id' => $customerId,
                'sold_by' => $soldBy,
                'total_amount' => $totalAmount,
                'payment_status' => $paymentMethod === 'Cash' ? 'paid' : 'unpaid',
                'status' => $paymentMethod === 'Cash' ? 'paid' : 'pending',
                'invoice_number' => 'temp'
            ]);

            // -----------------------------
            // Insert sale items
            // -----------------------------
            $now = now();
            $saleItemsData = [];
            foreach ($items as $item) {
                $product = $products[$item['product_id']];
                $unitPrice = $product->price;
                $discountPercent = $item['discount_percent'] ?? 0;
                $itemTotal = $unitPrice * $item['quantity'];
                $discountAmount = $itemTotal * ($discountPercent / 100);
                $saleItemsData[] = [
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'discount' => $discountAmount,
                    'total' => $itemTotal - $discountAmount,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            SaleItem::insert($saleItemsData);

            // -----------------------------
            // Generate invoice
            // -----------------------------
            $invoiceId = 'INV-' . date('Y') . '-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT);
            $sale->update(['invoice_number' => $invoiceId]);

            // -----------------------------
            // Handle Cash payment
            // -----------------------------
            if ($paymentMethod === 'Cash') {
                foreach ($items as $item) {
                    $product = $products[$item['product_id']];
                    $product->stock_quantity -= $item['quantity'];
                    $product->save();
                }

                Payment::create([
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'amount' => $totalAmount,
                    'payment_type' => 'income',
                    'payment_method' => 'Cash',
                    'paid_to_from' => 'Cash Payment',
                    'payment_date' => now(),
                    'bill_number' => $invoiceId,
                    'recorded_by' => $soldBy
                ]);

                $sale->refresh();
                DB::commit();

                return response()->json([
                    'status' => true,
                    'sale' => $sale,
                    'items' => $items,
                    'total_amount' => $totalAmount,
                    'invoice_number' => $invoiceId,
                    'payment_method' => 'Cash',
                    'message' => 'Sale completed and paid by Cash'
                ]);
            }

            // -----------------------------
            // Handle Bakong payment (static token)
            // -----------------------------
            $bakongAccount = env('BAKONG_ACCOUNT');
            $bakongToken = env('MY_BAKONG_TOKEN');

            $individualInfo = new IndividualInfo(
                bakongAccountID: $bakongAccount,
                merchantName: "RA THA Phearom",
                merchantCity: "Phnom Penh",
                currency: KHQRData::CURRENCY_KHR,
                amount: (int) round($totalAmount),
                billNumber: $invoiceId
            );

            $qrResponse = (new BakongKHQR($bakongToken))->generateIndividual($individualInfo);

            if (!$qrResponse->status || empty($qrResponse->data['qr'])) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to generate Bakong QR',
                    'error' => $qrResponse->error ?? 'Unknown error'
                ], 500);
            }

            // Save MD5 to sale for verification later
            $sale->update(['md5' => $qrResponse->data['md5']]);
            $sale->refresh();

            DB::commit();

            return response()->json([
                'status' => true,
                'sale' => $sale,
                'items' => $items,
                'total_amount' => $totalAmount,
                'invoice_number' => $invoiceId,
                'payment_method' => 'Bakong',
                'qr_string' => $qrResponse->data['qr'],
                'md5' => $qrResponse->data['md5'],
                'message' => 'Sale created. Awaiting Bakong payment.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // -----------------------------
    // Verify Bakong payment
    // -----------------------------
    public function verifySalePayment(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'md5' => 'required|string'
        ]);

        try {
            $sale = Sale::with('saleItems')->findOrFail($request->sale_id);

            $bakongAccount = env('BAKONG_ACCOUNT');

            if ($request->md5 !== $sale->md5) {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment not found or MD5 does not match'
                ], 400);
            }

            if ($sale->payment_status !== 'paid') {
                $sale->update([
                    'status' => 'paid',
                    'payment_status' => 'paid',
                    'payment_method' => 'Bakong'
                ]);

                // Reduce stock
                foreach ($sale->saleItems as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock_quantity -= $item->quantity;
                        $product->save();
                    }
                }

                Payment::create([
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'amount' => $sale->total_amount,
                    'payment_type' => 'income',
                    'payment_method' => 'Bakong',
                    'paid_to_from' => $bakongAccount,
                    'payment_date' => now(),
                    'bill_number' => $sale->invoice_number,
                    'recorded_by' => auth()->id()
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Payment verified successfully',
                'sale' => $sale
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to verify payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // -----------------------------
    // Other methods (index, destroy, dashboard) remain the same
    // -----------------------------
        public function index()
    {
        try {
            return Cache::remember('sales_list', 60, function () {
                $sales = Sale::with('customer:id,name', 'soldBy:id,name')->get();

                $salesData = $sales->map(function ($sale) {
                    $payment = Payment::where('reference_type', 'sale')
                                      ->where('reference_id', $sale->id)
                                      ->first();

                    return [
                        'id' => $sale->id,
                        'customer' => $sale->customer->name ?? '',
                        'invoice_number' => $sale->invoice_number,
                        'total_amount' => $sale->total_amount,
                        'discount' => $sale->discount,
                        'status' => $sale->status,
                        'payment_status' => $sale->payment_status,
                        'payment_method' => $payment->payment_method ?? null,
                        'sold_by' => $sale->soldBy->name ?? '',
                        'created_at' => $sale->created_at,
                        'payment_details' => $payment ? [
                            'status' => $sale->payment_status,
                            'method' => $payment->payment_method,
                            'amount' => $payment->amount,
                            'date' => $payment->payment_date
                        ] : null
                    ];
                });

                return response()->json([
                    'status' => 200,
                    'message' => 'Sales retrieved successfully',
                    'data' => $salesData
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // -----------------------------
    // Delete a sale and restore stock
    // -----------------------------
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::with('saleItems')->findOrFail($id);

            // Restore stock
            foreach ($sale->saleItems as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock_quantity += $item->quantity;
                    $product->save();
                }
            }

            // Delete sale items
            SaleItem::where('sale_id', $sale->id)->delete();

            // Delete payments
            Payment::where('reference_type', 'sale')->where('reference_id', $sale->id)->delete();

            // Delete sale
            $sale->delete();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'module' => 'sales',
                'record_id' => $id
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sale deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // -----------------------------
    // Dashboard: Today's revenue & sales history
    // -----------------------------
    public function dashboard()
    {
        try {
            return Cache::remember('sales_dashboard', 60, function () {
                $todayRevenue = Sale::whereDate('created_at', now()->toDateString())
                                    ->sum('total_amount');

                $todaySales = Sale::with('customer', 'soldBy')
                                  ->whereDate('created_at', now()->toDateString())
                                  ->get();

                return response()->json([
                    'status' => 200,
                    'message' => 'Today\'s sales summary retrieved successfully',
                    'data' => [
                        'total_revenue_today' => $todayRevenue,
                        'sales_history_today' => $todaySales
                    ]
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
