<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-FAPI-04 step 1: request a password reset link.
 *
 * Deliberately no `exists:users,email` rule. The endpoint must answer
 * identically whether or not the address is registered, and an exists rule
 * would turn a 422 field error into an account-enumeration oracle.
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
