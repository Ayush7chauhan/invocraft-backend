<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base Form Request for all API endpoints.
 *
 * – Overrides failedValidation() so validation errors are always returned
 *   as a JSON 422 response instead of a redirect.
 * – All child requests return true from authorize() — route-level middleware
 *   already handles authentication.
 */
abstract class BaseApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return a structured JSON 422 on validation failure.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Shared custom error messages that can be overridden in child requests.
     */
    public function messages(): array
    {
        return [
            'required'   => 'The :attribute field is required.',
            'string'     => 'The :attribute must be a valid string.',
            'numeric'    => 'The :attribute must be a number.',
            'integer'    => 'The :attribute must be a whole number.',
            'min'        => 'The :attribute must be at least :min.',
            'max'        => 'The :attribute must not exceed :max characters.',
            'digits'     => 'The :attribute must be exactly :digits digits.',
            'date'       => 'The :attribute must be a valid date.',
            'in'         => 'The selected :attribute is invalid.',
            'exists'     => 'The selected :attribute does not exist.',
            'unique'     => 'The :attribute has already been taken.',
            'email'      => 'The :attribute must be a valid email address.',
            'array'      => 'The :attribute must be an array.',
        ];
    }
}
