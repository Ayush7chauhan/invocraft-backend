<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'party_id' => 'required|exists:parties,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,upi,bank_transfer,cheque,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = $request->user();
            
            // Create payment
            $payment = Payment::create([
                'user_id' => $user->id,
                'party_id' => $request->party_id,
                'invoice_id' => $request->invoice_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
            ]);
            
            // Update invoice if linked
            if ($request->invoice_id) {
                $invoice = Invoice::findOrFail($request->invoice_id);
                $newPaidAmount = $invoice->paid_amount + $request->amount;
                
                if ($newPaidAmount >= $invoice->total_amount) {
                    $invoice->payment_status = 'paid';
                    $invoice->paid_amount = $invoice->total_amount;
                } elseif ($newPaidAmount > 0) {
                    $invoice->payment_status = 'partially_paid';
                    $invoice->paid_amount = $newPaidAmount;
                }
                
                $invoice->save();
            }
            
            // Create transaction entry
            Transaction::create([
                'user_id' => $user->id,
                'party_id' => $request->party_id,
                'type' => 'debit',
                'amount' => $request->amount,
                'transaction_date' => $request->payment_date,
                'note' => $request->notes ?? 'Payment' . ($request->invoice_id ? ' for Invoice' : ''),
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
            ]);
            
            DB::commit();
            
            $payment->load(['party', 'invoice']);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $payment
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }
}


