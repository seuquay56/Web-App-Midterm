<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Return 403 is false
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Returns 422 if any fields fails.
        return [
            'name' => ['required', 'string', 'max:255'],
            // unique:users,email checks the user table for that email
            'email' => ['required', 'string', 'max:255', 'unique:users,email'],
            // confirmed: requires a matching password_confirmation field
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
