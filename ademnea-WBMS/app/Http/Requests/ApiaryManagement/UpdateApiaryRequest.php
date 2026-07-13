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
            'contact_person_name' => ['nullable', 'string', 'max:150'],
            'contact_person_phone' => ['nullable', 'string', 'max:20'],
            'contact_person_email' => ['nullable', 'email', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'hive_capacity' => ['sometimes', 'required', 'integer', 'min:0'],
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
            'hive_capacity' => 'hive capacity',
            'contact_person_phone' => 'contact phone number',
            'contact_person_email' => 'contact email address',
        ];
    }
}
