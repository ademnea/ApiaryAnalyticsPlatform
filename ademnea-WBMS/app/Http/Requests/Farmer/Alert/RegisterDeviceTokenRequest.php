<?php

namespace App\Http\Requests\Farmer\Alert;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQ-F-FAPI-27: Register/update the farmer's FCM device token.
 * Note: fcm_token is treated as sensitive — never logged, never echoed back.
 */
class RegisterDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Protected route — auth:sanctum + role:farmer middleware already applies
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:255'],
        ];
    }
}