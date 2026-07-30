<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeamProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'role' => [
                'required',
                'string',
                'max:255',
            ],

            'institution' => [
                'required',
                'string',
                'max:255',
            ],

            'biography' => [
                'required',
                'string',
            ],

            'research_interests' => [
                'nullable',
                'string',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'profile_photo' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'status' => [
                'required',
                'in:Draft,Published,Archived',
            ],

            'display_order' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * Friendly Attribute Names
     */
    public function attributes(): array
    {
        return [

            'full_name' => 'Full Name',

            'profile_photo' => 'Profile Photo',

            'display_order' => 'Display Order',

            'research_interests' => 'Research Interests',
        ];
    }
}