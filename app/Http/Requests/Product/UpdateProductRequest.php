<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $userId    = $this->user()?->id;
        $productId = $this->route('product');

        return [
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'sku'                 => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products')->where('user_id', $userId)->ignore($productId)->whereNull('deleted_at'),
            ],
            'barcode'             => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products')->where('user_id', $userId)->ignore($productId)->whereNull('deleted_at'),
            ],
            'category_id'         => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id'             => ['nullable', 'integer', 'exists:units,id'],
            'purchase_price'      => ['sometimes', 'numeric', 'min:0'],
            'selling_price'       => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity'      => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'tax_rate'            => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status'              => ['nullable', 'in:' . implode(',', ProductStatus::values())],
        ];
    }
}
