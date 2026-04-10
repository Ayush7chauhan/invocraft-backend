<?php

namespace App\Http\Requests\Unit;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $userId = $this->user()?->id;
        $unitId = $this->route('unit');

        return [
            'name'       => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('units')->where('user_id', $userId)->ignore($unitId),
            ],
            'short_name'        => ['sometimes', 'required', 'string', 'max:20'],
            'type'              => ['sometimes', 'nullable', 'string', 'in:mass,volume,count,length,other'],
            'base_unit'         => ['sometimes', 'nullable', 'string', 'max:20'],
            'conversion_factor' => ['sometimes', 'nullable', 'numeric', 'min:0.000001'],
        ];
    }
}
