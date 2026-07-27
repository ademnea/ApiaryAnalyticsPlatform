<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-hives');
    }

    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'required', 'string', 'max:150'],
            'hive_type' => ['sometimes', 'required', 'in:TopBar,Langstroth,Warre,Kenya,Other'],
            'construction_material' => ['nullable', 'string', 'max:100'],
            'installation_date' => ['nullable', 'date'],
            'colony_origin' => ['nullable', 'in:Wild Capture,Split,Package,NUC,Unknown'],
            'queen_status' => ['nullable', 'in:Present,Absent,New,Old,Superseded,Unknown'],
            'last_inspection_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
