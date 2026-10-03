<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-FAPI-04 step 2: complete the password reset.
 *
 * As with ForgotPasswordRequest, there is no `exists:users,email` rule: an
 * unknown address must produce the same single "invalid or expired link"
 * message as a bad token, not a distinguishable field error.
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
            'email'    => ['required', 'email'],
            'token'    => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min'       => 'The password must be at least 8 characters.',
        ];
    }
}
