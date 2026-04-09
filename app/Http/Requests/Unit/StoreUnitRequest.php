<?php

namespace App\Http\Requests\Unit;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name'       => [
                'required',
                'string',
                'max:100',
                Rule::unique('units')->where('user_id', $userId),
            ],
            'short_name' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.unique' => 'This unit already exists.',
        ]);
    }
}
