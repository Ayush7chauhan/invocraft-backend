<?php

namespace App\Http\Controllers;

use App\Models\PersonalContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonalContactController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $relationship = $request->query('relationship');
        $status = $request->query('status');

        $query = PersonalContact::where('user_id', $user->id);

        if ($relationship) {
            $query->where('relationship', $relationship);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $contacts = $query->orderBy('name')->get();

        // Add balance calculations
        $contacts->each(function ($contact) {
            $contact->balance = $contact->balance;
            $contact->you_owe = $contact->you_owe;
            $contact->they_owe = $contact->they_owe;
        });

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'relationship' => 'required|in:friend,family,colleague,neighbor,other',
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

        $contact = PersonalContact::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'address' => $request->address,
            'relationship' => $request->relationship,
            'opening_balance' => $request->opening_balance ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        $contact->balance = $contact->balance;
        $contact->you_owe = $contact->you_owe;
        $contact->they_owe = $contact->they_owe;

        return response()->json([
            'success' => true,
            'message' => 'Contact created successfully',
            'data' => $contact
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $contact = PersonalContact::where('user_id', $request->user()->id)
            ->with(['transactions' => function($query) {
                $query->orderBy('transaction_date', 'desc');
            }])
            ->findOrFail($id);

        $contact->balance = $contact->balance;
        $contact->you_owe = $contact->you_owe;
        $contact->they_owe = $contact->they_owe;

        return response()->json([
            'success' => true,
            'data' => $contact
        ]);
    }

    public function update(Request $request, $id)
    {
        $contact = PersonalContact::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'mobile' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'relationship' => 'sometimes|in:friend,family,colleague,neighbor,other',
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

        $contact->update($request->only([
            'name', 'mobile', 'email', 'address', 'relationship', 'opening_balance', 'status'
        ]));

        $contact->balance = $contact->balance;
        $contact->you_owe = $contact->you_owe;
        $contact->they_owe = $contact->they_owe;

        return response()->json([
            'success' => true,
            'message' => 'Contact updated successfully',
            'data' => $contact
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $contact = PersonalContact::where('user_id', $request->user()->id)->findOrFail($id);
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully'
        ]);
    }
}


