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

    /**
     * Strip empty/null file slots from the images array before validation.
     *
     * Browsers can submit empty file inputs as null entries inside images[].
     * Leaving them in causes 'images.0 failed to upload' even when no file
     * was actually selected, because the 'file' rule rejects null values.
     */
    protected function prepareForValidation(): void
    {
        $files = $this->file('images', []);

        if (is_array($files)) {
            // Keep only entries that are actual UploadedFile instances.
            $clean = array_values(array_filter(
                $files,
                fn ($f) => $f instanceof \Illuminate\Http\UploadedFile
            ));

            // Replace the files bag entry so the validator sees the clean array.
            // An empty array is fine — the 'images' rule is nullable.
            // FileBag::set() does not accept null, so always pass an array.
            $this->files->set('images', $clean);
        }
    }

    public function rules(): array
    {
        $galleryId = $this->route('gallery')?->id;

        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'category'     => ['nullable', 'string', 'max:100'],
            'visibility'   => ['required', Rule::in(['public', 'private'])],
            'is_published' => ['nullable', 'boolean'],
            'slug'         => ['nullable', 'string', 'max:255', Rule::unique('gallery_albums')->ignore($galleryId)],
            'cover_image'  => ['nullable', 'file', 'image', 'max:10240'],
            'images'       => ['nullable', 'array'],
            'images.*'     => ['file', 'image', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'cover_image.image' => 'The cover image must be a valid image file.',
            'cover_image.max'   => 'The cover image may not be larger than 10 MB.',
            'images.*.image'    => 'Each uploaded file must be a valid image.',
            'images.*.max'      => 'Each image may not be larger than 10 MB.',
        ];
    }
}
