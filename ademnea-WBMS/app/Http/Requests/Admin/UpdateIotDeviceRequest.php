<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIotDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-iot-devices');
    }

    public function rules(): array
    {
        return [
            // device_code is intentionally NOT editable here — it is the
            // field-printed physical label; changing it after deployment
            // would desync the device from its own hardware.
            'device_type' => ['required', Rule::in(['numeric_sensor', 'media_capture', 'combo'])],
            'hardware_team_id' => ['required', 'exists:iot_hardware_teams,id'],
            'hive_id' => ['nullable', 'exists:hives,id'],
            'expected_interval_minutes' => ['required', 'integer', 'min:1'],
            'hardware_revision' => ['nullable', 'string', 'max:30'],
            'firmware_notes' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['provisioned', 'deployed', 'offline', 'retired'])],
        ];
    }
}