<?php

namespace App\Http\Controllers;

use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type'); // customer, supplier, both
        
        $query = Party::where('user_id', $user->id);
        
        if ($type && in_array($type, ['customer', 'supplier', 'both'])) {
            if ($type === 'both') {
                $query->where('type', 'both');
            } else {
                $query->where(function($q) use ($type) {
                    $q->where('type', $type)->orWhere('type', 'both');
                });
            }
        }
        
        $parties = $query->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $parties
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:1000',
            'type' => 'required|in:customer,supplier,both',
            'opening_balance' => 'nullable|numeric',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $party = Party::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'type' => $request->type,
            'opening_balance' => $request->opening_balance ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Party created successfully',
            'data' => $party
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $party = Party::where('user_id', $request->user()->id)
            ->withCount(['transactions', 'invoices', 'payments'])
            ->with(['transactions', 'invoices', 'payments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $party
        ]);
    }

    public function update(Request $request, $id)
    {
        $party = Party::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'mobile' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:1000',
            'type' => 'sometimes|in:customer,supplier,both',
            'opening_balance' => 'nullable|numeric',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $party->update($request->only([
            'name', 'mobile', 'address', 'type', 'opening_balance', 'status'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Party updated successfully',
            'data' => $party
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $party = Party::where('user_id', $request->user()->id)
            ->withCount(['transactions', 'invoices', 'payments'])
            ->findOrFail($id);

        // Count related records
        $transactionCount = $party->transactions_count ?? $party->transactions()->count();
        $invoiceCount = $party->invoices_count ?? $party->invoices()->count();
        $paymentCount = $party->payments_count ?? $party->payments()->count();

        // Delete all related transactions first (cascade delete)
        if ($transactionCount > 0) {
            $party->transactions()->delete();
        }

        // Delete all related invoices
        if ($invoiceCount > 0) {
            $party->invoices()->delete();
        }

        // Delete all related payments
        if ($paymentCount > 0) {
            $party->payments()->delete();
        }

        // Finally delete the party
        $party->delete();

        return response()->json([
            'success' => true,
            'message' => 'Party and all associated data deleted successfully',
            'data' => [
                'deleted_transactions' => $transactionCount,
                'deleted_invoices' => $invoiceCount,
                'deleted_payments' => $paymentCount,
            ]
        ]);
    }
}

