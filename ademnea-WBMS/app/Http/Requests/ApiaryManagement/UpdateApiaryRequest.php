<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-apiaries');
    }

    public function rules(): array
    {
        return [
            'farmer_id' => ['nullable', 'integer', 'exists:farmers,id'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'country' => ['sometimes', 'required', 'string', 'size:2'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:Active,Inactive,Under Maintenance'],
        ];
    }

    public function attributes(): array
    {
        return [
            'farmer_id' => 'managing farmer',
            'name' => 'apiary name',
            'country' => 'country of deployment',
        ];
    }
}
