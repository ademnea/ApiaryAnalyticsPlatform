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
            'country' => ['nullable', 'string', 'size:2'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'farmer_id' => ['nullable', 'integer', 'exists:farmers,id'],
            'contact_person_name' => ['nullable', 'string', 'max:150'],
            'contact_person_phone' => ['nullable', 'string', 'max:20'],
            'contact_person_email' => ['nullable', 'email', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'hive_capacity' => ['nullable', 'integer', 'min:0'],
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
            'hive_capacity' => 'hive capacity',
            'contact_person_phone' => 'contact phone number',
            'contact_person_email' => 'contact email address',
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
