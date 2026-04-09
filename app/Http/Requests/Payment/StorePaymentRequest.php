<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Http\Requests\BaseApiRequest;

class StorePaymentRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'party_id'         => ['required', 'integer', 'exists:parties,id'],
            'invoice_id'       => ['nullable', 'integer', 'exists:invoices,id'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_date'     => ['required', 'date'],
            'payment_method'   => ['required', 'in:' . implode(',', PaymentMethod::values())],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
