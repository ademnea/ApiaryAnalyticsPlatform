<?php

namespace Database\Factories;

use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IotDeviceFactory extends Factory
{
    protected $model = IotDevice::class;

    public function definition(): array
    {
        return [
            'device_code' => 'AEU-' . strtoupper($this->faker->unique()->bothify('??-###')),
            'device_type' => $this->faker->randomElement(['numeric_sensor', 'media_capture', 'combo']),
            'hardware_team_id' => IotHardwareTeam::factory(),
            'hive_id' => null,
            'api_key_hash' => Hash::make(Str::random(64)),
            'expected_interval_minutes' => 5,
            'status' => 'provisioned',
            'active_flag' => true,
        ];
    }
}