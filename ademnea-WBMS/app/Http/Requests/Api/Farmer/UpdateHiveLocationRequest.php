<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A farmer setting a hive's position from the phone they are holding next
 * to it. Hive ownership is checked in the controller, as for the other
 * hive-scoped endpoints.
 */
class UpdateHiveLocationRequest extends FormRequest
{
    /** A fix less precise than this could be the wrong hive, or the wrong apiary. */
    public const MAX_ACCURACY_METERS = 100;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'        => ['required', 'numeric', 'between:-90,90'],
            'longitude'       => ['required', 'numeric', 'between:-180,180'],
            // The radius the phone reports for its fix. Optional: older clients may not send it.
            'accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:'.self::MAX_ACCURACY_METERS],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between'    => 'Latitude must be between -90 and 90 degrees.',
            'longitude.between'   => 'Longitude must be between -180 and 180 degrees.',
            'accuracy_meters.max' => 'This location is not precise enough (it must be within '.self::MAX_ACCURACY_METERS.' m). Stand next to the hive under open sky and try again.',
        ];
    }
}
