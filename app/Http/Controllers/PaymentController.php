<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/payments
     * Query params: party_id, start_date, end_date, payment_method
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Payment::where('user_id', $user->id)
            ->with(['party:id,name', 'invoice:id,invoice_number']);

        if ($partyId = $request->query('party_id')) {
            $query->where('party_id', $partyId);
        }

        if ($method = $request->query('payment_method')) {
            $query->where('payment_method', $method);
        }

        if ($start = $request->query('start_date')) {
            $query->whereDate('payment_date', '>=', $start);
        }

        if ($end = $request->query('end_date')) {
            $query->whereDate('payment_date', '<=', $end);
        }

        $payments = $query->orderByDesc('payment_date')->get();

        return $this->successResponse($payments, 'Payments retrieved successfully');
    }

    /**
     * POST /api/payments
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'user_id'          => $user->id,
                'party_id'         => $data['party_id'],
                'invoice_id'       => $data['invoice_id'] ?? null,
                'amount'           => $data['amount'],
                'payment_date'     => $data['payment_date'],
                'payment_method'   => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            // ── Update linked invoice payment status ─────────────────────────
            if (!empty($data['invoice_id'])) {
                $invoice       = Invoice::findOrFail($data['invoice_id']);
                $newPaid       = (float) $invoice->paid_amount + (float) $data['amount'];
                $newPaid       = min($newPaid, (float) $invoice->total_amount);
                $invoice->paid_amount     = $newPaid;
                $invoice->payment_status  = match(true) {
                    $newPaid >= (float) $invoice->total_amount => 'paid',
                    $newPaid > 0                               => 'partially_paid',
                    default                                    => 'unpaid',
                };
                $invoice->save();
            }

            // ── Ledger entry (debit = money received from party) ─────────────
            Transaction::create([
                'user_id'          => $user->id,
                'party_id'         => $data['party_id'],
                'type'             => 'debit',
                'amount'           => $data['amount'],
                'transaction_date' => $data['payment_date'],
                'note'             => $data['notes'] ?? ('Payment received' . (!empty($data['invoice_id']) ? ' for invoice' : '')),
                'reference_type'   => 'payment',
                'reference_id'     => $payment->id,
            ]);

            DB::commit();

            return $this->createdResponse(
                $payment->load(['party:id,name', 'invoice:id,invoice_number']),
                'Payment recorded successfully'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverErrorResponse();
        }
    }
}
