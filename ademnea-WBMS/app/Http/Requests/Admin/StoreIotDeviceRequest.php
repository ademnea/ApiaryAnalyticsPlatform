<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIotDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return  true;//$this->user()->can('manage-iot-devices');
    }

    public function rules(): array
    {
        return [
            'device_code' => ['required', 'string', 'max:50', 'unique:iot_devices,device_code'],
            'device_type' => ['required', Rule::in(['numeric_sensor', 'media_capture', 'combo'])],
            'hardware_team_id' => ['required', 'exists:iot_hardware_teams,id'],
            'hive_id' => ['nullable', 'exists:hives,id'],
            'expected_interval_minutes' => ['nullable', 'integer', 'min:1'],
            'hardware_revision' => ['nullable', 'string', 'max:30'],
            'firmware_notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}