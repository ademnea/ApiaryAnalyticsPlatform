<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class FarmerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', 'unique:farmers,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'region' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:50', 'unique:farmers,national_id'],
            'status' => ['nullable', 'in:Active,Inactive,Suspended'],
            'registration_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A farmer with this email is already registered.',
            'national_id.unique' => 'A farmer with this national ID is already registered.',
        ];
    }
}
