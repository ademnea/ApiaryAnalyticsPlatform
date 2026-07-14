<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $galleryId = $this->route('gallery')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'is_published' => ['nullable', 'boolean'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('gallery_albums')->ignore($galleryId)],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
