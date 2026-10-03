<?php

namespace App\Http\Requests\Admin;

use App\Models\Hive;
use App\Services\Anomaly\DetectionLimitService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDetectionLimitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-hives');
    }

    public function rules(): array
    {
        $forHive = filled($this->input('hive_id'));

        $rules = [
            'hive_id' => ['nullable', 'integer', 'exists:hives,id'],
            'limits' => ['required', 'array'],
        ];

        foreach (app(DetectionLimitService::class)->fields() as $key => $field) {
            // A hive may leave a field empty to inherit the fleet value.
            $presence = $forHive ? 'nullable' : 'required';

            $rules["limits.{$key}"] = $field['type'] === 'version'
                ? [$presence, 'string', 'max:30', 'regex:/^v?\d+(\.\d+)*$/i']
                : [$presence, $field['step'] == 1 ? 'integer' : 'numeric', "between:{$field['min']},{$field['max']}"];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return collect(app(DetectionLimitService::class)->fields())
            ->mapWithKeys(fn (array $field, string $key) => ["limits.{$key}" => '"'.$field['label'].'"'])
            ->all();
    }

    public function messages(): array
    {
        return ['limits.latest_firmware_version.regex' => 'Enter a version number such as 1.4.2, or 0 to turn the check off.'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = app(DetectionLimitService::class);
            $fields = $service->fields();
            $values = $service->effectiveValues($this->hive(), $this->input('limits', []));

            foreach (DetectionLimitService::ORDERED_PAIRS as [$lower, $upper]) {
                if ((float) $values[$lower] >= (float) $values[$upper]) {
                    $validator->errors()->add("limits.{$lower}", "\"{$fields[$lower]['label']}\" must be below \"{$fields[$upper]['label']}\" ({$values[$upper]}).");
                }
            }
        });
    }

    public function hive(): ?Hive
    {
        return filled($this->input('hive_id')) ? Hive::find($this->input('hive_id')) : null;
    }
}
