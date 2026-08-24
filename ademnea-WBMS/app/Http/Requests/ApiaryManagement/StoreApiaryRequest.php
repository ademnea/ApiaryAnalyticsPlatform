<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-apiaries');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('apiaries', 'name')->where(function ($query) {
                    return $query
                        ->where('country', $this->input('country', 'UG'))
                        ->where('farmer_id', $this->input('farmer_id'));
                }),
            ],
            'country' => ['required', 'string', 'size:2'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'farmer_id' => ['nullable', 'integer', 'exists:farmers,id'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:Active,Inactive,Under Maintenance'],
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

    public function messages(): array
    {
        return [
            'name.unique' => 'This farmer already has an apiary with this name in this country.',
            'farmer_id.exists' => 'The selected farmer does not exist.',
        ];
    }
}
