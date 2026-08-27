<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-harvests');
    }

    public function rules(): array
    {
        return [
            'hive_id' => ['required', 'integer', 'exists:hives,id'],
            'harvest_date' => ['required', 'date'],
            'harvested_by' => ['nullable', 'integer', 'exists:users,id'],
            'honey_yield_kg' => ['nullable', 'numeric', 'min:0'],
            'beeswax_yield_kg' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
