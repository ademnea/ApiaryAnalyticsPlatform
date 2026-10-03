<?php

namespace App\Http\Requests\Api\Farmer;

use App\Http\Requests\Api\Farmer\Concerns\HasTelephoneRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-FAPI-05: edit own profile.
 *
 * `email` and `role` are absent by design — changing either requires admin
 * intervention to keep account integrity, so they are not merely ignored
 * downstream, they never reach validated() in the first place.
 */
class UpdateProfileRequest extends FormRequest
{
    use HasTelephoneRule;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'gender'     => ['nullable', 'string', 'in:Male,Female,Other'],
            'telephone'  => $this->telephoneRules(),
            'address'    => ['nullable', 'string', 'max:255'],
            'password'   => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return $this->telephoneMessages() + [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min'       => 'The password must be at least 8 characters.',
            'address.max'        => 'The address cannot exceed 255 characters.',
        ];
    }
}
