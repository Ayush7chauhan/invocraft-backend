<?php

namespace App\Http\Requests\Invoice;

use App\Http\Requests\BaseApiRequest;

class StoreInvoiceRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'party_id'           => ['required', 'integer', 'exists:parties,id'],
            'invoice_date'       => ['required', 'date'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'paid_amount'        => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string', 'max:2000'],

            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'   => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'items.required'             => 'At least one invoice item is required.',
            'items.min'                  => 'At least one invoice item is required.',
            'items.*.product_id.required'=> 'Each item must have a product.',
            'items.*.product_id.exists'  => 'One or more products do not exist.',
            'items.*.quantity.required'  => 'Each item must have a quantity.',
            'items.*.quantity.min'       => 'Item quantity must be at least 1.',
            'items.*.unit_price.required'=> 'Each item must have a price.',
            'items.*.unit_price.min'     => 'Item price cannot be negative.',
        ]);
    }
}
