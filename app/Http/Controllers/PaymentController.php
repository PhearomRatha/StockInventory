<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payments as Payment;
use App\Models\Sales as Sale;
use App\Helpers\ResponseHelper;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * FIXED: added select() and with() for better performance
     * Get all payments
     */
    public function index()
    {
        try {
            // OPTIMIZED: Add pagination to prevent returning too many records
            $payments = Payment::select('id', 'reference_type', 'reference_id', 'amount', 'payment_type', 'payment_method', 'paid_to_from', 'payment_date', 'status', 'recorded_by')
                ->with(['recordedBy:id,name'])
                ->latest()
                ->limit(100)
                ->get();
            
            return ResponseHelper::success('Payments retrieved successfully', $payments);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Create a new payment
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'reference' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $payment = Payment::create($validated);
            ActivityLogHelper::log('payment', "Payment #{$payment->id}: {$validated['amount']} via {$validated['payment_method']}");

            // Clear payment cache
            Cache::forget('payment_dashboard');

            return ResponseHelper::success('Payment created successfully', $payment, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Update a payment
     */
    public function update(Request $request, $id)
    {
        try {
            $payment = Payment::findOrFail($id);

            $validated = $request->validate([
                'status' => 'sometimes|required|string',
                'notes' => 'nullable|string'
            ]);

            $payment->update($validated);
            return ResponseHelper::success('Payment updated successfully', $payment);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete a payment
     */
    public function destroy($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment->delete();
            return ResponseHelper::success('Payment deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get payments dashboard
     */
    public function dashboard()
    {
        try {
            // OPTIMIZED: Cache dashboard for 5 minutes
            $data = Cache::remember('payment_dashboard', 300, function () {
                // OPTIMIZED: Single query with aggregation
                $paymentStats = Payment::selectRaw(
                    "
                    COUNT(*) as total_payments,
                    COALESCE(SUM(amount), 0) as total_amount
                    "
                )->first();

                $recentPayments = Payment::latest()->take(10)->get();

                return [
                    'total_payments' => $paymentStats->total_payments,
                    'total_amount' => $paymentStats->total_amount,
                    'recent_payments' => $recentPayments
                ];
            });

            return ResponseHelper::success('Payment dashboard data retrieved successfully', $data);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Checkout payment
     */
    public function checkoutPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'reference' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            // Use DB transaction to ensure data consistency
            $payment = DB::transaction(function () use ($validated) {
                $payment = Payment::create([
                    'sale_id' => $validated['sale_id'],
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'],
                    'reference' => $validated['reference'] ?? null,
                    'status' => 'completed',
                    'notes' => $validated['notes'] ?? null
                ]);

                // Update sale payment status
                Sale::where('id', $validated['sale_id'])->update([
                    'payment_status' => 'paid',
                    'status' => 'completed'
                ]);

                ActivityLogHelper::log('payment', "Payment #{$payment->id}: {$validated['amount']} via {$validated['payment_method']}");

                return $payment;
            });

            // Clear payment cache
            Cache::forget('payment_dashboard');

            return ResponseHelper::success('Payment successful', $payment, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'payment_id' => 'required|exists:payments,id',
                'reference' => 'required|string'
            ]);

            // Use DB transaction to ensure data consistency
            $payment = DB::transaction(function () use ($validated) {
                $payment = Payment::findOrFail($validated['payment_id']);
                $payment->update([
                    'status' => 'completed',
                    'reference' => $validated['reference']
                ]);

                // Also update the related sale status
                Sale::where('id', $payment->sale_id)->update([
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'payment_reference' => $validated['reference']
                ]);

                return $payment;
            });

            return ResponseHelper::success('Payment verified successfully', $payment);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
