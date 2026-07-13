<?php

namespace App\Http\Requests\Farmer\Message;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQ-F-FAPI-31: Submit a message to admin.
 */
class SubmitMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hive_id' => ['nullable', 'integer', 'exists:hives,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ];
    }
}