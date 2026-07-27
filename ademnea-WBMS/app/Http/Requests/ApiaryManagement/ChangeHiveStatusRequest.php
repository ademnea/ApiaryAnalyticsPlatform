<?php

namespace App\Http\Requests\ApiaryManagement;

use Illuminate\Foundation\Http\FormRequest;

class ChangeHiveStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:Active,Inactive,Under Inspection,Queenless,Absconded,Decommissioned'],
            'change_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
