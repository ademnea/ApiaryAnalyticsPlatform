<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('manage-newsletter');
    }

    public function rules(): array
    {
        $newsletter = $this->route('newsletter');

        return [
            'title' => 'required|string|max:255|unique:newsletters,title,' . $newsletter->id,
            'description' => 'required|string|max:500',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ];
    }
}
