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
            'title' => ['required', 'string', 'max:255', Rule::unique('gallery_albums')->ignore($galleryId)],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'is_published' => ['nullable', 'boolean'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('gallery_albums')->ignore($galleryId)],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'cover_image_index' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:30720'],
            'deleted_images' => ['nullable', 'array'],
            'deleted_images.*' => ['integer', 'exists:gallery_images,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter an album title.',
            'title.string' => 'The album title must be valid text.',
            'title.max' => 'The album title may not exceed 255 characters.',
            'title.unique' => 'An album with this title already exists. Please choose a different name.',
            'description.string' => 'The description must be valid text.',
            'category.string' => 'Please select a valid category.',
            'category.max' => 'The category may not exceed 100 characters.',
            'visibility.required' => 'Please choose whether this album is public or private.',
            'visibility.in' => 'The selected visibility value is invalid.',
            'is_published.boolean' => 'The published status is invalid.',
            'slug.unique' => 'An album with this URL slug already exists. Please choose a different title.',
            'cover_image.file' => 'The cover image must be a valid file.',
            'cover_image.mimes' => 'Only JPG, JPEG, PNG and WEBP images are allowed for the cover.',
            'cover_image.max' => 'The cover image must not exceed 30 MB.',
            'images.array' => 'The images field is invalid.',
            'images.*.file' => 'The selected file is not an image.',
            'images.*.mimes' => 'Only JPG, JPEG, PNG and WEBP images are allowed.',
            'images.*.max' => 'Each image must not exceed 30 MB.',
            'cover_image_index.integer' => 'Please select a valid cover image.',
            'cover_image_index.min' => 'Please select a valid cover image.',
            'deleted_images.array' => 'The deleted images selection is invalid.',
            'deleted_images.*.integer' => 'Please select a valid image to delete.',
            'deleted_images.*.exists' => 'One or more selected images do not exist.',
        ];
    }
}
