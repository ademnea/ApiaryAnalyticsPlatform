<?php

namespace App\Http\Requests\Farmer\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQ-F-FAPI-04 step 1: Request a password reset link.
 */
class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}