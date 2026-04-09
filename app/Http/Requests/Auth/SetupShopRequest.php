<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class SetupShopRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'user_id'                => ['required', 'integer', 'exists:users,id'],
            'owner_name'             => ['required', 'string', 'min:2', 'max:255'],
            'shop_name'              => ['nullable', 'string', 'max:255'],
            'shop_address'           => ['nullable', 'string', 'max:1000'],
            'business_type'          => ['nullable', 'string', 'max:50'],
            'gst_number'             => ['nullable', 'string', 'max:20'],
            'is_registration_complete' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'user_id.required'    => 'User ID is required.',
            'user_id.exists'      => 'Invalid user.',
            'owner_name.required' => 'Owner name is required.',
            'owner_name.min'      => 'Owner name must be at least 2 characters.',
        ]);
    }
}
