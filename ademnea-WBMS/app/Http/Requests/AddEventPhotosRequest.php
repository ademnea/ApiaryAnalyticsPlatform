<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddEventPhotosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('manage-events');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'photos.required' => 'At least one photo must be selected.',
            'photos.*.image' => 'Each file must be an image.',
            'photos.*.mimes' => 'Images must be JPG, JPEG, PNG, WEBP, or GIF.',
            'photos.*.max' => 'Each image must not exceed 5 MB.',
        ];
    }
}
