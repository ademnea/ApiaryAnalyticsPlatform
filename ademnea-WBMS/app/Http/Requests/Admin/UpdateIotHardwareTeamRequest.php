<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIotHardwareTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-iot-devices');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'country' => ['required', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}