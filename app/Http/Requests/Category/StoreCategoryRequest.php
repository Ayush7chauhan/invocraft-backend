<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\BaseApiRequest;

class StoreCategoryRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            // Unique per user (case-insensitive enforced at controller level)
            'name' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('categories')->where('user_id', $userId),
            ],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.unique' => 'This category already exists.',
        ]);
    }
}
