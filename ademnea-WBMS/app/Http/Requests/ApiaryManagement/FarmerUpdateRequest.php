<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FarmerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $farmerId = $this->route('farmer')?->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('farmers', 'email')->ignore($farmerId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'country' => ['sometimes', 'required', 'string', 'size:2'],
            'region' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'national_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('farmers', 'national_id')->ignore($farmerId),
            ],
            'status' => ['sometimes', 'required', 'in:Active,Inactive,Suspended'],
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
