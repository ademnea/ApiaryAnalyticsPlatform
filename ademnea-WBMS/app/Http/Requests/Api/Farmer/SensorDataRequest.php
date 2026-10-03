<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQ-F-FAPI-14 to 18: query filters for the sensor reading endpoints.
 *
 * The live contract (and docs/api/farmer-api.md) uses `from` / `to`. The SRS
 * §4.8.2.3 calls them `start` / `end` and requires strict ISO 8601 UTC. Both
 * spellings are accepted here so the mobile client can move to the SRS naming
 * without a flag day; `from` / `to` remain canonical until that call is made.
 */
class SensorDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise the SRS spelling onto the canonical one before validation, so
     * a client may send either pair.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from', $this->input('start')),
            'to'   => $this->input('to', $this->input('end')),
        ]);
    }

    public function rules(): array
    {
        return [
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date'          => 'The from date must be a valid date.',
            'to.date'            => 'The to date must be a valid date.',
            'to.after_or_equal'  => 'The to date must be after or equal to the from date.',
            'per_page.integer'   => 'Per page must be an integer.',
            'per_page.min'       => 'Per page must be at least 1.',
            'per_page.max'       => 'Per page cannot exceed 100.',
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }
}
