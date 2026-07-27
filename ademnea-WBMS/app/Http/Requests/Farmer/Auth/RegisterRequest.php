<?php

namespace App\Http\Requests\Farmer\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQ-F-FAPI-01: Farmer self-registration.
 * Validates input before FarmerAuthService::register() runs.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint — no auth check needed to register
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:farmers,email'],
            'telephone' => ['required', 'string', 'max:20'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}