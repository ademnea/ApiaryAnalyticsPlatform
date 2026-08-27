<?php

namespace App\Http\Requests;

use App\Models\WorkPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkPackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $workPackage = $this->route('work_package');

        return [
            'wp_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('work_packages', 'wp_number')
                    ->ignore($workPackage),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'summary' => [
                'nullable',
                'string',
            ],

            'description' => [
                'required',
                'string',
            ],

            'objectives' => [
                'required',
                'string',
            ],

            'deliverables' => [
                'required',
                'string',
            ],

            'lead' => [
                'required',
                'string',
                'max:255',
            ],

            'partners' => [
                'nullable',
                'string',
            ],

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:30720',
            ],

            'status' => [
                'required',
                Rule::in(WorkPackage::STATUS_OPTIONS),
            ],

            'display_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}