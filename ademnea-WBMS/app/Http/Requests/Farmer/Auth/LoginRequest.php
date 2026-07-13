<?php

namespace App\Http\Requests\Farmer\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQ-F-FAPI-02: Farmer login.
 * Validates input before FarmerAuthService::login() runs.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}