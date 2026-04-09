<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\BaseApiRequest;

class UpdateSettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'invoice_prefix'       => ['nullable', 'string', 'max:20'],
            'invoice_start_number' => ['nullable', 'integer', 'min:1'],
            'currency'             => ['nullable', 'string', 'max:10'],
            'currency_symbol'      => ['nullable', 'string', 'max:5'],
            'timezone'             => ['nullable', 'string', 'max:50'],
            'date_format'          => ['nullable', 'string', 'max:20'],
            'tax_name'             => ['nullable', 'string', 'max:30'],
            'default_tax_rate'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'show_tax_on_invoice'  => ['nullable', 'boolean'],
            'invoice_footer_note'  => ['nullable', 'string', 'max:1000'],
        ];
    }
}
