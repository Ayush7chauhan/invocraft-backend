<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $partyId = $request->query('party_id');
        $type = $request->query('type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $query = Transaction::where('user_id', $user->id)
            ->with('party');
        
        if ($partyId) {
            $query->where('party_id', $partyId);
        }
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }
        
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'party_id' => 'required|exists:parties,id',
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'party_id' => $request->party_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'note' => $request->note,
            'reference_type' => 'manual',
        ]);

        $transaction->load('party');

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $transaction = Transaction::where('user_id', $user->id)
            ->with('party')
            ->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        $transaction = Transaction::where('user_id', $user->id)->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'party_id' => 'sometimes|required|exists:parties,id',
            'type' => 'sometimes|required|in:debit,credit',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'transaction_date' => 'sometimes|required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction->update($request->only([
            'party_id',
            'type',
            'amount',
            'transaction_date',
            'note'
        ]));

        $transaction->load('party');

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $transaction
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $transaction = Transaction::where('user_id', $user->id)->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully'
        ]);
    }
}


