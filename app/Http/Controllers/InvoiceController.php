<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('payment_status');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $query = Invoice::where('user_id', $user->id)
            ->with(['party', 'items.product']);
        
        if ($status) {
            $query->where('payment_status', $status);
        }
        
        if ($startDate) {
            $query->whereDate('invoice_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('invoice_date', '<=', $endDate);
        }
        
        $invoices = $query->orderBy('invoice_date', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'party_id' => 'required|exists:parties,id',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'payment_status' => 'nullable|in:paid,partially_paid,unpaid',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
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
            
            // Generate invoice number
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(Invoice::where('user_id', $user->id)->count() + 1, 4, '0', STR_PAD_LEFT);
            
            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            
            foreach ($request->items as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTax = ($itemSubtotal * ($item['tax_rate'] ?? 0)) / 100;
                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;
            }
            
            $totalAmount = $subtotal + $taxAmount;
            $paidAmount = $request->paid_amount ?? 0;
            
            // Determine payment status
            $paymentStatus = $request->payment_status ?? 'unpaid';
            if ($paidAmount >= $totalAmount) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partially_paid';
            }
            
            // Create invoice
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'party_id' => $request->party_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $request->invoice_date,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_status' => $paymentStatus,
                'paid_amount' => $paidAmount,
                'notes' => $request->notes,
            ]);
            
            // Create invoice items and update stock
            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $itemSubtotal = $itemData['quantity'] * $itemData['unit_price'];
                $itemTax = ($itemSubtotal * ($itemData['tax_rate'] ?? 0)) / 100;
                
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'tax_amount' => $itemTax,
                    'total' => $itemSubtotal + $itemTax,
                ]);
                
                // Update stock
                $product->decrement('stock_quantity', $itemData['quantity']);
                
                // Create stock movement
                StockMovement::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                ]);
            }
            
            // Create transaction entry
            Transaction::create([
                'user_id' => $user->id,
                'party_id' => $request->party_id,
                'type' => 'credit',
                'amount' => $totalAmount,
                'transaction_date' => $request->invoice_date,
                'note' => 'Invoice: ' . $invoiceNumber,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
            ]);
            
            DB::commit();
            
            $invoice->load(['party', 'items.product']);
            
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $invoice = Invoice::where('user_id', $request->user()->id)
            ->with(['party', 'items.product', 'payments'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $invoice
        ]);
    }
}


