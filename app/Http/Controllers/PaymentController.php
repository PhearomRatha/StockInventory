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
     * Get all payments
     */
    public function index()
    {
        try {
            $payments = Payment::with(['recordedBy:id,name', 'sale:id,invoice_number'])
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
                'reference_type' => 'required|in:sale,purchase',
                'reference_id' => 'required|exists:sales,id',
                'payment_type' => 'required|in:income,expense',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'paid_to_from' => 'nullable|string',
                'payment_date' => 'required|date',
                'status' => 'required|in:paid,pending',
                'notes' => 'nullable|string'
            ]);

            $payment = Payment::create([
                'sale_id' => $validated['reference_type'] === 'sale' ? $validated['reference_id'] : null,
                'type' => strtoupper($validated['payment_type']),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'paid_to_from' => $validated['paid_to_from'] ?? null,
                'payment_date' => $validated['payment_date'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $request->user()?->id ?? 1,
            ]);
            
            ActivityLogHelper::log('payment', "Payment #{$payment->id}: {$validated['amount']} via {$validated['payment_method']}");

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
                'reference_type' => 'sometimes|required|in:sale,purchase',
                'reference_id' => 'sometimes|required|exists:sales,id',
                'payment_type' => 'sometimes|required|in:income,expense',
                'amount' => 'sometimes|required|numeric|min:0',
                'payment_method' => 'sometimes|required|string',
                'paid_to_from' => 'nullable|string',
                'payment_date' => 'sometimes|required|date',
                'status' => 'sometimes|required|in:paid,pending',
                'notes' => 'nullable|string'
            ]);

            if (isset($validated['reference_type'])) {
                $validated['sale_id'] = $validated['reference_type'] === 'sale' ? $validated['reference_id'] : null;
                unset($validated['reference_type'], $validated['reference_id']);
            }
            
            if (isset($validated['payment_type'])) {
                $validated['type'] = strtoupper($validated['payment_type']);
                unset($validated['payment_type']);
            }

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
            $today = now()->toDateString();
            
            $income = Payment::where('type', 'INCOME')
                ->whereDate('payment_date', $today)
                ->sum('amount');
                
            $expense = Payment::where('type', 'EXPENSE')
                ->whereDate('payment_date', $today)
                ->sum('amount');

            $data = [
                'today_income' => (float) $income,
                'today_expense' => (float) $expense,
            ];

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

            $payment = DB::transaction(function () use ($validated) {
                $payment = Payment::create([
                    'sale_id' => $validated['sale_id'],
                    'type' => 'INCOME',
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'],
                    'reference_no' => $validated['reference'] ?? null,
                    'status' => 'completed',
                    'notes' => $validated['notes'] ?? null
                ]);

                Sale::where('id', $validated['sale_id'])->update([
                    'payment_status' => 'paid',
                    'status' => 'completed'
                ]);

                ActivityLogHelper::log('payment', "Payment #{$payment->id}: {$validated['amount']} via {$validated['payment_method']}");

                return $payment;
            });

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

            $payment = DB::transaction(function () use ($validated) {
                $payment = Payment::findOrFail($validated['payment_id']);
                $payment->update([
                    'status' => 'completed',
                    'reference_no' => $validated['reference']
                ]);

                if ($payment->sale_id) {
                    Sale::where('id', $payment->sale_id)->update([
                        'payment_status' => 'paid',
                        'status' => 'completed',
                        'payment_reference' => $validated['reference']
                    ]);
                }

                return $payment;
            });

            return ResponseHelper::success('Payment verified successfully', $payment);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}