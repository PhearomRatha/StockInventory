<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payments as Payment;
use App\Helpers\ResponseHelper;
use App\Helpers\ActivityLogHelper;

class PaymentController extends Controller
{
    /**
     * Get all payments
     */
    public function index()
    {
        try {
            $payments = Payment::all();
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
            $totalPayments = Payment::count();
            $totalAmount = Payment::sum('amount');
            $recentPayments = Payment::latest()->take(10)->get();

            return ResponseHelper::success('Payment dashboard data retrieved successfully', [
                'total_payments' => $totalPayments,
                'total_amount' => $totalAmount,
                'recent_payments' => $recentPayments
            ]);
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

            $payment = Payment::create([
                'sale_id' => $validated['sale_id'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null
            ]);

            ActivityLogHelper::log('payment', "Payment #{$payment->id}: {$validated['amount']} via {$validated['payment_method']}");

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

            $payment = Payment::findOrFail($validated['payment_id']);
            $payment->update([
                'status' => 'completed',
                'reference' => $validated['reference']
            ]);

            return ResponseHelper::success('Payment verified successfully', $payment);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
