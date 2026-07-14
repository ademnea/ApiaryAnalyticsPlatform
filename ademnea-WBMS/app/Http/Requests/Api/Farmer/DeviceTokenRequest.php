<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class DeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_token' => ['required', 'string', 'not_empty'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_token.required' => 'Device token is required.',
            'device_token.not_empty' => 'Device token cannot be empty.',
        ];
    }
}