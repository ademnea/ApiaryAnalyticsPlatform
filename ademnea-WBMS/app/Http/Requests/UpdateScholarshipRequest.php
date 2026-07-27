<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScholarshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'funding_type' => ['required', 'string', 'max:255'],
            'funding_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'description' => ['required', 'string'],
            'eligibility' => ['required', 'string'],
            'benefits' => ['required', 'string'],
            'application_procedure' => ['required', 'string'],
            'banner_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'attachment_files.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'status' => ['required', 'in:draft,active,expired'],
            'is_featured' => ['sometimes', 'boolean'],
            'application_deadline' => ['required', 'date', 'after:today'],
            'application_link' => ['nullable', 'url', 'max:255'],
        ];
    }
}
