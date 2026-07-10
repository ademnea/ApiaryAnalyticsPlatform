<?php

namespace Database\Factories;

use App\Models\IotHardwareTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

class IotHardwareTeamFactory extends Factory
{
    protected $model = IotHardwareTeam::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Field Team',
            'country' => $this->faker->randomElement(['Uganda', 'South Sudan', 'Tanzania']),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'is_active' => true,
        ];
    }
}