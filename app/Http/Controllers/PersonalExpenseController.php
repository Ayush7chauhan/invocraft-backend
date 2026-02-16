<?php

namespace App\Http\Controllers;

use App\Models\PersonalExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonalExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $category = $request->query('category');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = PersonalExpense::where('user_id', $user->id);

        if ($category) {
            $query->where('category', $category);
        }

        if ($startDate) {
            $query->where('expense_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('expense_date', '<=', $endDate);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $expenses
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string|max:50|in:food,travel,entertainment,shopping,bills,health,education,transport,gifts,electronics,clothing,groceries,furniture,appliances,books,sports,beauty,other',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,upi,card,bank_transfer,other',
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

        $expense = PersonalExpense::create([
            'user_id' => $request->user()->id,
            ...$request->only([
                'title', 'description', 'amount', 'category',
                'expense_date', 'payment_method', 'reference_number', 'notes'
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense recorded successfully',
            'data' => $expense
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $expense = PersonalExpense::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'category' => 'sometimes|string|max:50|in:food,travel,entertainment,shopping,bills,health,education,transport,gifts,electronics,clothing,groceries,furniture,appliances,books,sports,beauty,other',
            'expense_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|in:cash,upi,card,bank_transfer,other',
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

        $expense->update($request->only([
            'title', 'description', 'amount', 'category',
            'expense_date', 'payment_method', 'reference_number', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully',
            'data' => $expense
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $expense = PersonalExpense::where('user_id', $request->user()->id)->findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully'
        ]);
    }
}


