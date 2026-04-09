<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/expenses
     * Query params: category, start_date, end_date, payment_method
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Expense::where('user_id', $user->id);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($method = $request->query('payment_method')) {
            $query->where('payment_method', $method);
        }

        if ($start = $request->query('start_date')) {
            $query->whereDate('expense_date', '>=', $start);
        }

        if ($end = $request->query('end_date')) {
            $query->whereDate('expense_date', '<=', $end);
        }

        $expenses = $query->orderByDesc('expense_date')->get();

        return $this->successResponse($expenses, 'Expenses retrieved successfully');
    }

    /**
     * POST /api/expenses
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();

        $expense = Expense::create(array_merge($data, [
            'user_id'        => $request->user()->id,
            'payment_method' => $data['payment_method'] ?? 'cash',
        ]));

        return $this->createdResponse($expense, 'Expense added successfully');
    }

    /**
     * GET /api/expenses/{expense}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $expense = Expense::where('user_id', $request->user()->id)->findOrFail($id);

        return $this->successResponse($expense, 'Expense retrieved successfully');
    }

    /**
     * PUT /api/expenses/{expense}
     */
    public function update(UpdateExpenseRequest $request, string $id): JsonResponse
    {
        $expense = Expense::where('user_id', $request->user()->id)->findOrFail($id);
        $expense->update($request->validated());

        return $this->successResponse($expense->fresh(), 'Expense updated successfully');
    }

    /**
     * DELETE /api/expenses/{expense}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $expense = Expense::where('user_id', $request->user()->id)->findOrFail($id);
        $expense->delete(); // soft delete

        return $this->successResponse(null, 'Expense deleted successfully');
    }

    /**
     * GET /api/expenses/categories
     * Returns the distinct expense categories for this user.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = Expense::where('user_id', $request->user()->id)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return $this->successResponse($categories, 'Expense categories retrieved successfully');
    }
}
