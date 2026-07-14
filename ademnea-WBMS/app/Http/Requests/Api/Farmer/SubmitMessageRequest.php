<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'hive_id' => ['nullable', 'integer', 'exists:hives,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Subject is required.',
            'subject.max' => 'Subject cannot exceed 255 characters.',
            'message.required' => 'Message body is required.',
            'hive_id.exists' => 'The selected hive does not exist.',
        ];
    }
}