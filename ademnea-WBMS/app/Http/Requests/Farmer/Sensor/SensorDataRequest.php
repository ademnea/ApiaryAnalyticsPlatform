<?php

namespace App\Http\Requests\Farmer\Sensor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQ-F-FAPI-14 to 18: Sensor data read endpoints.
 * Supports optional ?from=&to= date filtering and ?per_page=.
 */
class SensorDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }
}