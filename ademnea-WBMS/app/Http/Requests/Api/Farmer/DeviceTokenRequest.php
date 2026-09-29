<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-FAPI-14: register the Firebase device token for push delivery.
 *
 * The token is sensitive — it is never logged and never echoed back in a
 * response. See the dontFlash list in bootstrap/app.php.
 */
class DeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_token' => ['bail', 'required', 'string', 'min:1', 'max:512'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_token.required' => 'The device token field is required.',
            'device_token.max'      => 'The device token is too long.',
        ];
    }
}
