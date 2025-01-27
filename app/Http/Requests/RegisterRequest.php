<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator; // Correct import for Validator

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Set to true so that the request is authorized
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|email|max:255|unique:users,email',
            'address' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'lga' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:255',
            'refer_by' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        // Customize the response format for validation failure
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'data' => $validator->errors(),
        ], 422));
    }
}
