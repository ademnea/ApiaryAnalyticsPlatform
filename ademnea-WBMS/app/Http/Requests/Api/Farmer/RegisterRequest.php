<?php

namespace App\Http\Requests\Api\Farmer;

use App\Http\Requests\Api\Farmer\Concerns\HasTelephoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC-FAPI-01: farmer self-registration.
 *
 * Uniqueness is checked against `users`, not `farmers`. An admin may already
 * have created a registry-only farmer row for this person, and registration
 * adopts that row rather than rejecting it — see AuthService::register().
 */
class RegisterRequest extends FormRequest
{
    use HasTelephoneRule;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            // whereNull('deleted_at') so a soft-deleted account does not
            // permanently burn its email address.
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'telephone'  => $this->telephoneRules(required: true),
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return $this->telephoneMessages() + [
            'email.unique'       => 'An account with this email already exists.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min'       => 'The password must be at least 8 characters.',
        ];
    }
}
