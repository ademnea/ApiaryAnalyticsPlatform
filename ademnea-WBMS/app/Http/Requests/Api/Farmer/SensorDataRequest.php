<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class SensorDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date', 'date_format:Y-m-d\TH:i:s\Z'],
            'end' => ['nullable', 'date', 'date_format:Y-m-d\TH:i:s\Z', 'after_or_equal:start'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'start.date_format' => 'Start date must be in ISO 8601 UTC format (Y-m-d\TH:i:s\Z).',
            'end.date_format' => 'End date must be in ISO 8601 UTC format (Y-m-d\TH:i:s\Z).',
            'end.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.integer' => 'Per page must be an integer.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
        ];
    }
}