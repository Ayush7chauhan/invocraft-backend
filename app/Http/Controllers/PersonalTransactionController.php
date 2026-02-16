<?php

namespace App\Http\Controllers;

use App\Models\PersonalTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonalTransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $contactId = $request->query('contact_id');
        $type = $request->query('type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = PersonalTransaction::where('user_id', $user->id)
            ->with('personalContact');

        if ($contactId) {
            $query->where('personal_contact_id', $contactId);
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
            'personal_contact_id' => 'required|exists:personal_contacts,id',
            'type' => 'required|in:given,received',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cash,upi,bank_transfer,other',
            'reference_number' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify contact belongs to user
        $contact = \App\Models\PersonalContact::where('user_id', $request->user()->id)
            ->findOrFail($request->personal_contact_id);

        $transaction = PersonalTransaction::create([
            'user_id' => $request->user()->id,
            'personal_contact_id' => $request->personal_contact_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'note' => $request->note,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
        ]);

        $transaction->load('personalContact');

        return response()->json([
            'success' => true,
            'message' => 'Transaction recorded successfully',
            'data' => $transaction
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $transaction = PersonalTransaction::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:given,received',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'transaction_date' => 'sometimes|required|date',
            'note' => 'nullable|string|max:1000',
            'payment_method' => 'sometimes|in:cash,upi,bank_transfer,other',
            'reference_number' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction->update($request->only([
            'type', 'amount', 'transaction_date', 'note', 'payment_method', 'reference_number'
        ]));

        $transaction->load('personalContact');

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $transaction
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $transaction = PersonalTransaction::where('user_id', $request->user()->id)->findOrFail($id);
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully'
        ]);
    }
}


