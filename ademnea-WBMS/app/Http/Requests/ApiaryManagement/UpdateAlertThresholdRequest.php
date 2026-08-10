<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlertThresholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-hives');
    }

    public function rules(): array
    {
        $thresholdId = $this->route('alertThreshold')->getKey();

        return [
            'key' => ['required', 'string', 'max:100', 'unique:alert_thresholds,key,' . $thresholdId],
            'value' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'hive_id' => ['nullable', 'exists:hives,id'],
        ];
    }
}
