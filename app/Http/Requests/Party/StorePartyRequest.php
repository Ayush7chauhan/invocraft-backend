<?php

namespace App\Http\Requests\Party;

use App\Enums\PartyType;
use App\Http\Requests\BaseApiRequest;

class StorePartyRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'mobile'          => ['nullable', 'digits:10'],
            'email'           => ['nullable', 'email', 'max:255'],
            'address'         => ['nullable', 'string', 'max:1000'],
            'gst_number'      => ['nullable', 'string', 'max:20'],
            'type'            => ['required', 'in:' . implode(',', PartyType::values())],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'status'          => ['nullable', 'in:active,inactive'],
        ];
    }
}
