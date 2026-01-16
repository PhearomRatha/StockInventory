<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Payments as Payment;
use App\Models\Sales as Sale;
use App\Models\Stock_ins as StockIn;
use App\Models\Activity_logs as ActivityLog;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class PaymentController extends Controller
{
    // -----------------------------
    // List all payments
    // -----------------------------
    public function index()
    {
        $payments = Payment::with('sale', 'stockIn')->get();
        return response()->json([
            'status' => 200,
            'message'=> 'Payments retrieved successfully',
            'data'   => $payments
        ], 200);
    }

    // -----------------------------
    // Dashboard: totals and history
    // -----------------------------
    public function dashboard()
    {
        return Cache::remember('payments_dashboard', 10, function () {
            $today = now()->toDateString();

            // Today’s income
            $totalIncome = Payment::where('payment_type', 'income')
                ->whereDate('payment_date', $today)
                ->sum('amount');

            // Today’s expense
            $totalExpense = Payment::where('payment_type', 'expense')
                ->whereDate('payment_date', $today)
                ->sum('amount');

            // Recent payments (last 10)
            $recentPayments = Payment::with('sale', 'stockIn')
                ->orderByDesc('payment_date')
                ->take(10)
                ->get();

            return response()->json([
                'status' => true,
                'today_income' => $totalIncome,
                'today_expense'=> $totalExpense,
                'recent_payments' => $recentPayments
            ]);
        });
    }

    // -----------------------------
    // Checkout payment via Bakong (like multi-item/multi-sale)
    // -----------------------------
    public function checkoutPayment(Request $request)
    {
        $request->validate([
            'reference_type'=>'required|in:sale,purchase',
            'reference_ids'=>'required|array',
            'reference_ids.*'=>'required|integer',
            'payment_method'=>'nullable|in:Bakong,Cash'
        ]);

        $bakongAccount = env('BAKONG_ACCOUNT');
        $bakongToken   = env('MY_BAKONG_TOKEN');

        if (!$bakongAccount || !$bakongToken) {
            return response()->json([
                'status'=>false,
                'message'=>'Bakong configuration missing'
            ],500);
        }

        $paymentMethod = $request->payment_method ?? 'Bakong';
        $payments = [];
        $totalAmount = 0;

        foreach($request->reference_ids as $id){
            $reference = $request->reference_type=='sale' ? Sale::find($id) : StockIn::find($id);
            if(!$reference) continue;

            $payment = Payment::create([
                'reference_type'=>$request->reference_type,
                'reference_id'=>$id,
                'amount'=>$reference->total_amount ?? $reference->amount,
                'payment_type'=>$request->reference_type=='sale'?'income':'expense',
                'payment_method'=>$paymentMethod,
                'paid_to_from'=>$paymentMethod === 'Bakong' ? $bakongAccount : 'Cash',
                'payment_date'=>now(),
                'status'=>$paymentMethod === 'Cash' ? 'paid' : 'pending'
            ]);

            $payments[] = $payment;
            $totalAmount += $payment->amount;
        }

        if(empty($payments)){
            return response()->json([
                'status'=>false,
                'message'=>'No valid references to pay'
            ],400);
        }

        if($paymentMethod === 'Cash'){
            foreach($payments as $payment){
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'created',
                    'module' => 'payments',
                    'record_id' => $payment->id
                ]);
            }
            return response()->json([
                'status'=>true,
                'payments'=>$payments,
                'total_amount'=>$totalAmount,
                'payment_method'=>'Cash',
                'message'=>'Payments completed via Cash'
            ]);
        }

        // Combine bill numbers if multiple payments
        $billNumbers = collect($payments)->pluck('bill_number')->implode(',');

        // Generate QR
        $individualInfo = new IndividualInfo(
            bakongAccountID: $bakongAccount,
            merchantName: "RA THA Phearom",
            merchantCity: "Phnom Penh",
            currency: KHQRData::CURRENCY_KHR,
            amount: $totalAmount,
            billNumber: $billNumbers
        );

        $qrResponse = (new BakongKHQR($bakongToken))->generateIndividual($individualInfo);

        foreach($payments as $payment){
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'created',
                'module' => 'payments',
                'record_id' => $payment->id
            ]);
        }

        return response()->json([
            'status'=>true,
            'payments'=>$payments,
            'total_amount'=>$totalAmount,
            'qr_string'=>$qrResponse->data['qr'] ?? null,
            'md5'=>$qrResponse->data['md5'] ?? null,
            'payment_method'=>'Bakong'
        ]);
    }

    // -----------------------------
    // Verify Bakong payment and update records
    // -----------------------------
    public function verifyPayment(Request $request)
    {
        $request->validate(['md5'=>'required|string']);

        $bakongToken = env('MY_BAKONG_TOKEN');
        if(!$bakongToken){
            return response()->json([
                'status'=>false,
                'message'=>'Bakong configuration missing'
            ],500);
        }

        $khqr = new BakongKHQR($bakongToken);
        $verify = $khqr->checkTransactionByMD5($request->md5);
        $verifyArray = is_array($verify)?$verify:(array)$verify;
        $data = $verifyArray['data'] ?? [];

        if(($verifyArray['responseCode'] ?? 1) !==0 || empty($data['acknowledgedDateMs'])){
            return response()->json([
                'status'=>false,
                'message'=>'Payment not found or unsuccessful',
                'bakong'=>$verifyArray
            ],400);
        }

        // Update payment record
        $payment = Payment::where('md5',$request->md5)->first();
        if($payment && $payment->status!='paid'){
            $payment->update([
                'status'=>'paid',
                'bill_number'=>$data['externalRef'] ?? $payment->bill_number
            ]);

            // If sale, update sale status too
            if($payment->reference_type=='sale'){
                $sale = Sale::find($payment->reference_id);
                if($sale) $sale->update(['status'=>'paid']);
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'verified',
                'module' => 'payments',
                'record_id' => $payment->id
            ]);
        }

        return response()->json([
            'status'=>true,
            'message'=>'Payment verified successfully',
            'payment'=>$payment,
            'bakong'=>$data
        ]);
    }

    // -----------------------------
    // Store new payment
    // -----------------------------
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'reference_type'=>'required|in:sale,purchase',
                'reference_id'=>'required|integer',
                'amount'=>'required|numeric',
                'payment_type'=>'required|in:income,expense',
                'payment_method'=>'nullable|string',
                'paid_to_from'=>'required|string',
                'payment_date'=>'required|date',
                'recorded_by'=>'required|exists:users,id'
            ]);

            // Check reference exists
            if($validated['reference_type']=='sale' && !Sale::find($validated['reference_id'])){
                return response()->json(['status'=>404,'message'=>'Sale not found'],404);
            }
            if($validated['reference_type']=='purchase' && !StockIn::find($validated['reference_id'])){
                return response()->json(['status'=>404,'message'=>'Stock in not found'],404);
            }

            $payment = Payment::create($validated);

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'created',
                'module' => 'payments',
                'record_id' => $payment->id
            ]);

            return response()->json([
                'status'=>201,
                'message'=>'Payment recorded successfully',
                'data'=>$payment
            ],201);

        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // -----------------------------
    // Update payment
    // -----------------------------
    public function update(Request $request, $id)
    {
        try {
            $payment = Payment::findOrFail($id);

            $validated = $request->validate([
                'reference_type'=>'sometimes|required|in:sale,purchase',
                'reference_id'=>'sometimes|required|integer',
                'amount'=>'sometimes|required|numeric',
                'payment_type'=>'sometimes|required|in:income,expense',
                'payment_method'=>'nullable|string',
                'paid_to_from'=>'sometimes|required|string',
                'payment_date'=>'sometimes|required|date',
                'recorded_by'=>'sometimes|required|exists:users,id'
            ]);

            $payment->update($validated);

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'module' => 'payments',
                'record_id' => $payment->id
            ]);

            return response()->json([
                'status'=>200,
                'message'=>'Payment updated successfully',
                'data'=>$payment
            ],200);

        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    // -----------------------------
    // Delete payment
    // -----------------------------
    public function destroy($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment->delete();

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'module' => 'payments',
                'record_id' => $id
            ]);

            return response()->json([
                'status'=>200,
                'message'=>'Payment deleted successfully'
            ],200);

        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
