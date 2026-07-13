<?php

namespace App\Http\Requests\Farmer\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQ-F-FAPI-04 step 2: Reset password using the emailed token.
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}