<?php

namespace App\Http\Requests\Expense;

use App\Enums\PaymentMethod;
use App\Http\Requests\BaseApiRequest;

class UpdateExpenseRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'title'            => ['sometimes', 'required', 'string', 'max:255'],
            'amount'           => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'expense_date'     => ['sometimes', 'required', 'date'],
            'category'         => ['nullable', 'string', 'max:100'],
            'payment_method'   => ['nullable', 'in:' . implode(',', PaymentMethod::values())],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'note'             => ['nullable', 'string', 'max:2000'],
        ];
    }
}
