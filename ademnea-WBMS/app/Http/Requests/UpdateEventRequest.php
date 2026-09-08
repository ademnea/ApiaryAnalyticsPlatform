<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'venue' => 'required|string|max:255',
            'description' => 'required|string',
            'event_date' => 'required|date|date_format:Y-m-d',
            'event_time' => 'nullable|date_format:H:i',
            'article_link' => 'nullable|url|max:500',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Event title is required.',
            'venue.required' => 'Event venue is required.',
            'description.required' => 'Event description is required.',
            'event_date.required' => 'Event date is required.',
            'event_date.date_format' => 'Event date must be in YYYY-MM-DD format.',
            'event_time.date_format' => 'Event time must be in HH:MM format.',
            'article_link.url' => 'Article link must be a valid URL.',
            'photos.*.image' => 'Each file must be an image.',
            'photos.*.mimes' => 'Images must be JPG, JPEG, PNG, WEBP, or GIF.',
            'photos.*.max' => 'Each image must not exceed 5 MB.',
        ];
    }
}
