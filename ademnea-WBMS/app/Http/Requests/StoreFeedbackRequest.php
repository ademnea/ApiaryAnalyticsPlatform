<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public form
    }

    public function rules(): array
    {
        return [
            'feedback_category_id' => ['nullable','exists:feedback_categories,id'],
            'full_name' => ['required','string','max:255'],
            'email' => ['required','email','max:255'],
            'phone' => ['nullable','string','max:50'],
            'organization' => ['nullable','string','max:255'],
            'subject' => ['required','string','max:255'],
            'message' => ['required','string'],
            'attachments.*' => ['nullable','file','mimes:pdf,doc,docx,png,jpg,jpeg,webp','max:10240'],
        ];
    }
}
