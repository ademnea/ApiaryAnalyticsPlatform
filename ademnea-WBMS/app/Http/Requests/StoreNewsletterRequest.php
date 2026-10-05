<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('manage-newsletter');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:newsletters,title',
            'description' => 'required|string|max:500',
            'content' => 'required|string',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ];
    }
}
