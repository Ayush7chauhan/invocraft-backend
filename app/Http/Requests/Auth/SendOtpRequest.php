<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class SendOtpRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'digits:10'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.digits'   => 'Mobile number must be exactly 10 digits.',
        ]);
    }
}
