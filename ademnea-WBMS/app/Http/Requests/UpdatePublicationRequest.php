<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('manage-publications');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $currentYear = now()->year;

        return [
            'author' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'publisher' => 'required|string|max:255',
            'publication_year' => "required|integer|min:1900|max:{$currentYear}",
            'description' => 'nullable|string|max:5000',
            'attachment' => 'nullable|mimes:pdf|max:10240', // 10 MB - optional for updates
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120', // 5 MB - optional for updates
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'author.required' => 'Author name is required.',
            'title.required' => 'Publication title is required.',
            'publisher.required' => 'Publisher name is required.',
            'publication_year.required' => 'Publication year is required.',
            'publication_year.min' => 'Year must be 1900 or later.',
            'publication_year.max' => 'Year cannot be in the future.',
            'attachment.mimes' => 'Attachment must be a PDF file.',
            'attachment.max' => 'PDF file size must not exceed 10 MB.',
            'image.image' => 'Cover image must be an image file.',
            'image.mimes' => 'Image must be JPG, JPEG, PNG, WEBP, or GIF.',
            'image.max' => 'Image size must not exceed 5 MB.',
        ];
    }
}
