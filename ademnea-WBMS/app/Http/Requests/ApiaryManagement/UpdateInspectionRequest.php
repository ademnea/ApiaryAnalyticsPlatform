<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-inspections');
    }

    public function rules(): array
    {
        return [
            'hive_id' => ['sometimes', 'required', 'integer', 'exists:hives,id'],
            'inspected_at' => ['sometimes', 'required', 'date'],
            'inspected_by' => ['nullable', 'integer', 'exists:users,id'],
            'strength_rating' => ['nullable', 'string', 'max:50'],
            'disease_events' => ['nullable', 'string'],
            'queen_status_notes' => ['nullable', 'string'],
            'general_notes' => ['nullable', 'string'],
        ];
    }
}
