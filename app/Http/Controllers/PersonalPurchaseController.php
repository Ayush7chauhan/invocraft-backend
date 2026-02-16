<?php

namespace App\Http\Controllers;

use App\Models\PersonalPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonalPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $category = $request->query('category');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = PersonalPurchase::where('user_id', $user->id);

        if ($category) {
            $query->where('category', $category);
        }

        if ($startDate) {
            $query->where('purchase_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('purchase_date', '<=', $endDate);
        }

        $purchases = $query->orderBy('purchase_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|in:electronics,clothing,groceries,furniture,appliances,books,sports,beauty,other',
            'purchase_date' => 'required|date',
            'payment_method' => 'required|in:cash,upi,card,bank_transfer,other',
            'store_name' => 'nullable|string|max:255',
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

        $purchase = PersonalPurchase::create([
            'user_id' => $request->user()->id,
            ...$request->only([
                'item_name', 'description', 'amount', 'category',
                'purchase_date', 'payment_method', 'store_name',
                'reference_number', 'notes'
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase recorded successfully',
            'data' => $purchase
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $purchase = PersonalPurchase::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'category' => 'sometimes|in:electronics,clothing,groceries,furniture,appliances,books,sports,beauty,other',
            'purchase_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|in:cash,upi,card,bank_transfer,other',
            'store_name' => 'nullable|string|max:255',
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

        $purchase->update($request->only([
            'item_name', 'description', 'amount', 'category',
            'purchase_date', 'payment_method', 'store_name',
            'reference_number', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Purchase updated successfully',
            'data' => $purchase
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $purchase = PersonalPurchase::where('user_id', $request->user()->id)->findOrFail($id);
        $purchase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase deleted successfully'
        ]);
    }
}


