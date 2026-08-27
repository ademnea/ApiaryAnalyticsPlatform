<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlertThresholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-hives');
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'unique:alert_thresholds,key'],
            'value' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'hive_id' => ['nullable', 'exists:hives,id'],
        ];
    }
}
