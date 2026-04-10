<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\BaseApiRequest;

class UpdateSettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            // ── Shop Profile (saved to User model) ────────────────────────────
            'shop_name'     => ['nullable', 'string', 'max:150'],
            'owner_name'    => ['nullable', 'string', 'max:150'],
            'shop_address'  => ['nullable', 'string', 'max:500'],
            'business_type' => ['nullable', 'string', 'in:grocery,medical,general,other'],
            'gst_number'    => ['nullable', 'string', 'max:20'],

            // ── Invoice / Billing Settings (saved to Setting model) ───────────
            'invoice_prefix'       => ['nullable', 'string', 'max:20'],
            'invoice_start_number' => ['nullable', 'integer', 'min:1'],
            'currency'             => ['nullable', 'string', 'max:10'],
            'currency_symbol'      => ['nullable', 'string', 'max:5'],
            'default_tax_rate'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'show_tax_on_invoice'  => ['nullable', 'boolean'],
            'invoice_footer_note'  => ['nullable', 'string', 'max:1000'],
        ];
    }
}
