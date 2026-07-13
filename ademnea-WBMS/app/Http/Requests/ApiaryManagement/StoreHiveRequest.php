<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreHiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-hives');
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:150'],
            'hive_type' => ['required', 'in:TopBar,Langstroth,Warre,Kenya,Other'],
            'construction_material' => ['nullable', 'string', 'max:100'],
            'installation_date' => ['nullable', 'date'],
            'colony_origin' => ['nullable', 'in:Wild Capture,Split,Package,NUC,Unknown'],
            'queen_status' => ['nullable', 'in:Present,Absent,New,Old,Superseded,Unknown'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'last_inspection_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'GPS latitude must be between -90 and 90 degrees.',
            'longitude.between' => 'GPS longitude must be between -180 and 180 degrees.',
            'queen_status.in' => 'Queen status must be one of: Present, Absent, New, Old, Superseded, Unknown.',
        ];
    }
}
