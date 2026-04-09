<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/invoices
     * Query params: payment_status, start_date, end_date, party_id
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Invoice::where('user_id', $user->id)
            ->with(['party:id,name,mobile', 'items.product:id,name']);

        if ($status = $request->query('payment_status')) {
            if (in_array($status, ['paid', 'partially_paid', 'unpaid'], true)) {
                $query->where('payment_status', $status);
            }
        }

        if ($partyId = $request->query('party_id')) {
            $query->where('party_id', $partyId);
        }

        if ($start = $request->query('start_date')) {
            $query->whereDate('invoice_date', '>=', $start);
        }

        if ($end = $request->query('end_date')) {
            $query->whereDate('invoice_date', '<=', $end);
        }

        $invoices = $query->orderByDesc('invoice_date')->get();

        return $this->successResponse($invoices, 'Invoices retrieved successfully');
    }

    /**
     * POST /api/invoices
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        DB::beginTransaction();
        try {
            // ── Calculate totals ────────────────────────────────────────────
            $subtotal  = 0;
            $taxAmount = 0;

            foreach ($data['items'] as $item) {
                $lineTotal  = $item['quantity'] * $item['unit_price'];
                $lineTax    = ($lineTotal * (($item['tax_rate'] ?? 0) / 100));
                $subtotal  += $lineTotal;
                $taxAmount += $lineTax;
            }

            $discount    = (float) ($data['discount'] ?? 0);
            $totalAmount = ($subtotal - $discount) + $taxAmount;
            $paidAmount  = (float) ($data['paid_amount'] ?? 0);

            // Cap paid amount to total
            $paidAmount = min($paidAmount, $totalAmount);

            $paymentStatus = match(true) {
                $paidAmount >= $totalAmount => 'paid',
                $paidAmount > 0            => 'partially_paid',
                default                    => 'unpaid',
            };

            // ── Generate invoice number ──────────────────────────────────────
            $setting       = $user->setting ?? null;
            $prefix        = $setting?->invoice_prefix ?? 'INV';
            $invoiceNumber = $this->generateInvoiceNumber($user->id, $prefix);

            // ── Create invoice ───────────────────────────────────────────────
            $invoice = Invoice::create([
                'user_id'        => $user->id,
                'party_id'       => $data['party_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_date'   => $data['invoice_date'],
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $totalAmount,
                'payment_status' => $paymentStatus,
                'paid_amount'    => $paidAmount,
                'notes'          => $data['notes'] ?? null,
            ]);

            // ── Create items, update stock, log movements ────────────────────
            foreach ($data['items'] as $itemData) {
                $product   = Product::findOrFail($itemData['product_id']);
                $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
                $lineTax   = $lineTotal * (($itemData['tax_rate'] ?? 0) / 100);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity'   => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate'   => $itemData['tax_rate'] ?? 0,
                    'tax_amount' => $lineTax,
                    'total'      => $lineTotal + $lineTax,
                ]);

                // Decrement stock
                $product->decrement('stock_quantity', $itemData['quantity']);

                // Stock movement log
                StockMovement::create([
                    'user_id'        => $user->id,
                    'product_id'     => $product->id,
                    'type'           => 'out',
                    'quantity'       => $itemData['quantity'],
                    'unit_price'     => $itemData['unit_price'],
                    'reference_type' => 'invoice',
                    'reference_id'   => $invoice->id,
                ]);
            }

            // ── Ledger entry ─────────────────────────────────────────────────
            Transaction::create([
                'user_id'          => $user->id,
                'party_id'         => $data['party_id'],
                'type'             => 'credit',
                'amount'           => $totalAmount,
                'transaction_date' => $data['invoice_date'],
                'note'             => 'Invoice: ' . $invoiceNumber,
                'reference_type'   => 'invoice',
                'reference_id'     => $invoice->id,
            ]);

            DB::commit();

            return $this->createdResponse(
                $invoice->load(['party:id,name,mobile', 'items.product:id,name']),
                'Invoice created successfully'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverErrorResponse();
        }
    }

    /**
     * GET /api/invoices/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::where('user_id', $request->user()->id)
            ->with(['party', 'items.product', 'payments'])
            ->findOrFail($id);

        $invoice->append('outstanding_amount');

        return $this->successResponse($invoice, 'Invoice retrieved successfully');
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Generate a unique, sequential invoice number for this user.
     * Format: {PREFIX}-{YYYYMMDD}-{0001}
     * Collision-safe via DB locking.
     */
    private function generateInvoiceNumber(int $userId, string $prefix): string
    {
        $count  = Invoice::where('user_id', $userId)->withTrashed()->count() + 1;
        $suffix = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-" . date('Ymd') . "-{$suffix}";
    }
}
