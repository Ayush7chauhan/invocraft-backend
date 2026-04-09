<?php

namespace App\Http\Requests\Expense;

use App\Enums\PaymentMethod;
use App\Http\Requests\BaseApiRequest;

class StoreExpenseRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'expense_date'     => ['required', 'date'],
            'category'         => ['nullable', 'string', 'max:100'],
            'payment_method'   => ['nullable', 'in:' . implode(',', PaymentMethod::values())],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'note'             => ['nullable', 'string', 'max:2000'],
        ];
    }
}
